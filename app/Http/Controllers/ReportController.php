<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function weeklyExams(Request $request)
    {
        $class = $request->input('class', 'hsc_1');
        $section = $request->input('section', 'science');
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput) : null;
        $subject = $request->input('subject', 'all');

        $marks = WeeklyExamMark::with('student')
            ->where('class_level', $class)
            ->where('section', $section)
            ->when($subject !== 'all', fn ($q) => $q->where('subject', $subject))
            ->when($date, fn ($q) => $q->whereDate('exam_date', $date))
            ->orderBy('exam_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.weekly-exams', [
            'marks' => $marks,
            'classLabel' => AcademyOptions::classLabel($class),
            'sectionLabel' => AcademyOptions::sectionLabel($section),
            'date' => $date,
            'subject' => $subject,
            'subjectLabel' => $subject === 'all' ? 'All Subjects' : AcademyOptions::subjectLabel($subject),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("weekly-exams-{$class}-{$section}.pdf");
    }
    public function weeklyExamsExcel(Request $request)
    {
        $class = $request->input('class', 'hsc_1');
        $section = $request->input('section', 'science');
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput) : null;
        $subject = $request->input('subject', 'all');

        $marks = WeeklyExamMark::with('student')
            ->where('class_level', $class)
            ->where('section', $section)
            ->when($subject !== 'all', fn ($q) => $q->where('subject', $subject))
            ->when($date, fn ($q) => $q->whereDate('exam_date', $date))
            ->orderBy('exam_date')
            ->get();

        $filename = 'weekly-exams-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        $columns = ['Student', 'Class', 'Section', 'Subject', 'Exam Date', 'Marks', 'Max Marks', 'Remarks'];

        $callback = function () use ($columns, $marks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($marks as $mark) {
                fputcsv($handle, [
                    $mark->student->name,
                    AcademyOptions::classLabel($mark->class_level),
                    AcademyOptions::sectionLabel($mark->section),
                    AcademyOptions::subjectLabel($mark->subject),
                    optional($mark->exam_date)->format('d M Y'),
                    $mark->marks_obtained,
                    $mark->max_marks,
                    $mark->remarks,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function dueList(Request $request)
    {
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');
        $year = $request->input('year', '');

        $students = Student::query()
            ->with(['feePayments' => fn ($q) => $q->orderBy('payment_date')])
            ->when($class !== 'all', fn ($q) => $q->where('class_level', $class))
            ->when($section !== 'all', fn ($q) => $q->where('section', $section))
            ->when($year, fn ($q) => $q->where('academic_year', 'like', '%' . $year . '%'))
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $summary = $student->dueSummary();
                $student->outstanding = $summary['amount'];
                $student->due_months = implode(', ', $summary['months']);
                return $student;
            })
            ->filter(fn (Student $student) => $student->outstanding > 0)
            ->values();

        $pdf = Pdf::loadView('reports.pdf.due-list', [
            'students' => $students,
            'filters' => [
                'class' => $class,
                'section' => $section,
                'year' => $year,
            ],
            'totalDue' => $students->sum('outstanding'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('due-list.pdf');
    }

    public function finance(Request $request)
    {
        $startInput = $request->input('start');
        $endInput = $request->input('end');

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : now()->startOfMonth();
        $end = $endInput ? Carbon::parse($endInput)->endOfDay() : now()->endOfMonth();

        $payments = FeePayment::with('student')
            ->whereBetween('payment_date', [$start, $end])
            ->orderBy('payment_date')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.finance', [
            'payments' => $payments,
            'expenses' => $expenses,
            'start' => $start,
            'end' => $end,
            'incomeTotal' => $payments->sum('amount'),
            'expenseTotal' => $expenses->sum('amount'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('finance-ledger.pdf');
    }

    public function financeExcel(Request $request)
    {
        $startInput = $request->input('start');
        $endInput = $request->input('end');

        $start = $startInput ? Carbon::parse($startInput)->startOfDay() : now()->startOfMonth();
        $end = $endInput ? Carbon::parse($endInput)->endOfDay() : now()->endOfMonth();

        $payments = FeePayment::with('student')
            ->whereBetween('payment_date', [$start, $end])
            ->orderBy('payment_date')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date')
            ->get();

        $filename = 'finance-ledger-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($payments, $expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'Date', 'Description', 'Amount', 'Receipt']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    'Income',
                    optional($payment->payment_date)->format('d M Y'),
                    $payment->student->name . ' (' . AcademyOptions::classLabel($payment->student->class_level ?? '') . ', ' . AcademyOptions::sectionLabel($payment->student->section ?? '') . ') - ' . ($payment->payment_mode ?? 'N/A'),
                    number_format($payment->amount, 2),
                    $payment->receipt_number ?? 'N/A',
                ]);
            }

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    'Expense',
                    optional($expense->expense_date)->format('d M Y'),
                    $expense->category . ($expense->description ? ' - ' . $expense->description : ''),
                    number_format($expense->amount, 2),
                    '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function dueListExcel(Request $request)
    {
        $class = $request->input('class', 'all');
        $section = $request->input('section', 'all');
        $year = $request->input('year', '');

        $students = Student::query()
            ->with(['feePayments' => fn ($q) => $q->orderBy('payment_date')])
            ->when($class !== 'all', fn ($q) => $q->where('class_level', $class))
            ->when($section !== 'all', fn ($q) => $q->where('section', $section))
            ->when($year, fn ($q) => $q->where('academic_year', 'like', '%' . $year . '%'))
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $summary = $student->dueSummary();
                return [
                    'name' => $student->name,
                    'class' => AcademyOptions::classLabel($student->class_level),
                    'section' => AcademyOptions::sectionLabel($student->section),
                    'phone' => $student->phone_number,
                    'outstanding' => $summary['amount'],
                    'due_months' => implode(', ', $summary['months']),
                ];
            })
            ->filter(fn ($row) => $row['outstanding'] > 0)
            ->values();

        $filename = 'due-list-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Student Name', 'Class', 'Section', 'Phone', 'Outstanding', 'Due Months'];

        $callback = function () use ($columns, $students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($students as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['class'],
                    $row['section'],
                    $row['phone'],
                    number_format($row['outstanding'], 2),
                    $row['due_months'],
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function weeklyExamsStudent(Request $request)
    {
        $class = $request->input('class');
        $section = $request->input('section');
        $studentId = $request->input('student_id');
        $month = $request->input('month', now()->format('Y-m'));

        abort_if(! $class || ! $section || ! $studentId, 404);

        $student = Student::where('class_level', $class)
            ->where('section', $section)
            ->findOrFail($studentId);

        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $marks = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->whereBetween('exam_date', [$start, $end])
            ->orderBy('exam_date')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.student-weekly-exams', [
            'student' => $student,
            'classLabel' => AcademyOptions::classLabel($class),
            'sectionLabel' => AcademyOptions::sectionLabel($section),
            'monthLabel' => $monthDate->format('F Y'),
            'marks' => $marks,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("weekly-exams-{$student->id}-{$monthDate->format('Ym')}.pdf");
    }

    public function weeklyExamsStudentExcel(Request $request)
    {
        $class = $request->input('class');
        $section = $request->input('section');
        $studentId = $request->input('student_id');
        $month = $request->input('month', now()->format('Y-m'));

        abort_if(! $class || ! $section || ! $studentId, 404);

        $student = Student::where('class_level', $class)
            ->where('section', $section)
            ->findOrFail($studentId);

        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();

        $marks = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->whereBetween('exam_date', [$start, $end])
            ->orderBy('exam_date')
            ->get();

        $filename = 'weekly-exams-' . $student->id . '-' . $monthDate->format('Ym') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Date', 'Subject', 'Marks', 'Max Marks', 'Remarks'];

        $callback = function () use ($columns, $marks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($marks as $mark) {
                fputcsv($handle, [
                    optional($mark->exam_date)->format('d M Y'),
                    AcademyOptions::subjectLabel($mark->subject),
                    $mark->marks_obtained,
                    $mark->max_marks,
                    $mark->remarks,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
