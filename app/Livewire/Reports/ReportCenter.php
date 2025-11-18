<?php

namespace App\Livewire\Reports;

use App\Models\Attendance;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Livewire\Component;

class ReportCenter extends Component
{
    public string $examClass = 'hsc_1';
    public string $examSection = 'science';
    public string $examSubject = 'all';
    public string $examDate = '';

    public string $dueClass = 'all';
    public string $dueSection = 'all';
    public string $dueYear = '';

    public string $financeRangeStart;
    public string $financeRangeEnd;

    public string $studentReportClass = 'hsc_1';
    public string $studentReportSection = 'science';
    public string $studentReportMonth;
    public ?int $studentReportStudentId = null;

    public function mount(): void
    {
        $this->examDate = now()->format('Y-m-d');
        $this->financeRangeStart = now()->startOfMonth()->format('Y-m-d');
        $this->financeRangeEnd = now()->endOfMonth()->format('Y-m-d');
        $this->studentReportMonth = now()->format('Y-m');
    }

    public function render()
    {
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $attendanceThisMonth = Attendance::whereBetween('attendance_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $totalFees = FeePayment::sum('amount');

        $outstanding = FeeInvoice::outstanding()->get()->sum(fn ($invoice) => $invoice->amount_due - $invoice->amount_paid);
        $weeklyMarks = WeeklyExamMark::count();

        $subjectOptions = AcademyOptions::subjectsForSection($this->examSection);
        if ($this->examSubject !== 'all' && ! array_key_exists($this->examSubject, $subjectOptions)) {
            $this->examSubject = 'all';
        }

        $studentReportOptions = Student::query()
            ->where('status', 'active')
            ->where('class_level', $this->studentReportClass)
            ->where('section', $this->studentReportSection)
            ->orderBy('name')
            ->get();

        return view('livewire.reports.report-center', [
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'attendanceCount' => $attendanceThisMonth,
            'feeCollected' => $totalFees,
            'outstandingFees' => $outstanding,
            'weeklyMarks' => $weeklyMarks,
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => AcademyOptions::sections(),
            'subjectOptions' => ['all' => 'All Subjects'] + $subjectOptions,
            'studentReportOptions' => $studentReportOptions,
        ]);
    }

    public function downloadExamReport()
    {
        return redirect()->route('reports.weekly-exams.pdf', [
            'class' => $this->examClass,
            'section' => $this->examSection,
            'date' => $this->examDate,
            'subject' => $this->examSubject,
        ]);
    }

    public function downloadExamExcel()
    {
        return redirect()->route('reports.weekly-exams.excel', [
            'class' => $this->examClass,
            'section' => $this->examSection,
            'date' => $this->examDate,
            'subject' => $this->examSubject,
        ]);
    }

    public function updatedExamSection(): void
    {
        $this->examSubject = 'all';
    }

    public function downloadDueReport()
    {
        return redirect()->route('reports.due-list.pdf', [
            'class' => $this->dueClass,
            'section' => $this->dueSection,
            'year' => $this->dueYear,
        ]);
    }

    public function downloadDueExcel()
    {
        return redirect()->route('reports.due-list.excel', [
            'class' => $this->dueClass,
            'section' => $this->dueSection,
            'year' => $this->dueYear,
        ]);
    }

    public function downloadFinanceReport()
    {
        return redirect()->route('reports.finance.pdf', [
            'start' => $this->financeRangeStart,
            'end' => $this->financeRangeEnd,
        ]);
    }

    public function downloadFinanceExcel()
    {
        return redirect()->route('reports.finance.excel', [
            'start' => $this->financeRangeStart,
            'end' => $this->financeRangeEnd,
        ]);
    }

    public function downloadStudentExamReport()
    {
        if (! $this->studentReportStudentId) {
            return;
        }

        return redirect()->route('reports.weekly-exams.student.pdf', [
            'class' => $this->studentReportClass,
            'section' => $this->studentReportSection,
            'student_id' => $this->studentReportStudentId,
            'month' => $this->studentReportMonth,
        ]);
    }

    public function downloadStudentExamExcel()
    {
        if (! $this->studentReportStudentId) {
            return;
        }

        return redirect()->route('reports.weekly-exams.student.excel', [
            'class' => $this->studentReportClass,
            'section' => $this->studentReportSection,
            'student_id' => $this->studentReportStudentId,
            'month' => $this->studentReportMonth,
        ]);
    }

    public function updatedStudentReportClass(): void
    {
        $this->studentReportStudentId = null;
    }

    public function updatedStudentReportSection(): void
    {
        $this->studentReportStudentId = null;
    }
}
