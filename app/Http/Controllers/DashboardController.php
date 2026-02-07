<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\ManualIncome;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use App\Models\WeeklyExamMark;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Make sure current month invoices exist (and are adjusted) so totals refresh without visiting Fees/Due pages
        Student::chunkById(200, function ($students) use ($startOfMonth) {
            foreach ($students as $student) {
                $student->ensureInvoicesThroughMonth($startOfMonth);
                if ($invoice = $student->feeInvoices()->where('billing_month', $startOfMonth->toDateString())->first()) {
                    $student->adjustInvoiceForAttendance($invoice);
                }
            }
        });

        $totalIncomeMonth = FeePayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $totalExpensesMonth = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $outstandingDues = FeeInvoice::whereColumn('amount_paid', '<', 'amount_due')
            ->selectRaw('SUM(amount_due - amount_paid) as outstanding')
            ->value('outstanding') ?? 0;
        $dueThresholdExceeded = $outstandingDues > 100000;

        $financialSnapshot = [
            'income' => $totalIncomeMonth,
            'expenses' => $totalExpensesMonth,
            'net' => $totalIncomeMonth - $totalExpensesMonth,
            'outstanding' => $outstandingDues,
            'thresholdExceeded' => $dueThresholdExceeded,
        ];

        $studentCounts = [
            'total' => Student::where('is_passed', false)->count(),
            'active' => Student::where('status', 'active')->where('is_passed', false)->count(),
            'inactive' => Student::where('status', 'inactive')->where('is_passed', false)->count(),
            'passed' => Student::where('is_passed', true)->count(),
        ];

        $classDistribution = Student::select('class_level', DB::raw('count(*) as total'))
            ->where('is_passed', false)
            ->groupBy('class_level')
            ->pluck('total', 'class_level');

        $recentPayments = collect();
        foreach (FeePayment::with('student')->latest('payment_date')->take(4)->get() as $payment) {
            $recentPayments->push([
                'type' => 'fee',
                'date' => $payment->payment_date,
                'model' => $payment,
            ]);
        }
        foreach (ManualIncome::where('category', 'Admission Fee')->latest('income_date')->take(4)->get() as $income) {
            $recentPayments->push([
                'type' => 'admission',
                'date' => $income->income_date,
                'model' => $income,
            ]);
        }
        $recentPayments = $recentPayments->sortByDesc('date')->take(4)->values();

        $recentActivities = [
            'students' => Student::latest()->take(4)->get(),
            'payments' => $recentPayments,
            'notes' => StudentNote::with('student')->latest('note_date')->take(4)->get(),
            'expenses' => Expense::latest('expense_date')->take(4)->get(),
            'instructors' => User::where('role', 'instructor')->latest()->take(4)->get(),
        ];

        $attendanceToday = Attendance::select('students.class_level', 'attendances.status', DB::raw('count(*) as total'))
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->whereDate('attendance_date', $now->toDateString())
            ->groupBy('students.class_level', 'attendances.status')
            ->get()
            ->groupBy('class_level');

        $examHealth = WeeklyExamMark::select('class_level', 'section', DB::raw('AVG(CASE WHEN max_marks > 0 THEN (marks_obtained / max_marks) * 100 ELSE 0 END) as average'))
            ->where('exam_date', '>=', $now->copy()->subWeek())
            ->groupBy('class_level', 'section')
            ->get();

        $overdueThresholdDate = $now->copy()->subMonths(2)->endOfMonth();
        $overdueStudentIds = FeeInvoice::whereColumn('amount_paid', '<', 'amount_due')
            ->where('billing_month', '<=', $overdueThresholdDate)
            ->pluck('student_id')
            ->unique();
        $overdueHighlights = Student::whereIn('id', $overdueStudentIds)->with('feeInvoices')->take(5)->get();

        $pendingInstructorIds = User::where('role', 'instructor')
            ->where('is_active', true)
            ->whereDoesntHave('weeklyExamMarks', function ($query) use ($now) {
                $query->where('exam_date', '>=', $now->copy()->subDays(7));
            })
            ->pluck('name');

        $notifications = [
            'overdueStudents' => $overdueHighlights,
            'pendingInstructors' => $pendingInstructorIds,
        ];

        $classPerformance = WeeklyExamMark::select('class_level', 'section', DB::raw('AVG(CASE WHEN max_marks > 0 THEN (marks_obtained / max_marks) * 100 ELSE 0 END) as average'))
            ->whereBetween('exam_date', [$startOfMonth, $endOfMonth])
            ->groupBy('class_level', 'section')
            ->orderBy('class_level')
            ->get();

        $frequentAbsentees = Attendance::select('student_id', DB::raw('count(*) as total'))
            ->where('status', 'absent')
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->groupBy('student_id')
            ->having('total', '>=', 3)
            ->with('student')
            ->get();

        $recentExamMarks = WeeklyExamMark::with('student')
            ->latest('exam_date')
            ->take(5)
            ->get();

        $invoiceUpdateAlerts = AuditLog::with('user')
            ->where('action', 'invoice.update')
            ->latest()
            ->take(5)
            ->get();

        $instructorStudentAlerts = Student::query()
            ->with(['feeInvoices' => function ($query) {
                $query->whereColumn('amount_paid', '<', 'amount_due');
            }])
            ->get()
            ->filter(function (Student $student) {
                return $student->feeInvoices->isNotEmpty();
            })
            ->take(5);

        return view('dashboard', [
            'user' => $user,
            'financialSnapshot' => $financialSnapshot,
            'studentCounts' => $studentCounts,
            'classDistribution' => $classDistribution,
            'recentActivities' => $recentActivities,
            'attendanceToday' => $attendanceToday,
            'examHealth' => $examHealth,
            'notifications' => $notifications,
            'classPerformance' => $classPerformance,
            'frequentAbsentees' => $frequentAbsentees,
            'recentExamMarks' => $recentExamMarks,
            'invoiceUpdateAlerts' => $invoiceUpdateAlerts,
            'instructorStudentAlerts' => $instructorStudentAlerts,
        ]);
    }
}
