<?php

namespace App\Livewire\Students;

use App\Models\Routine;
use App\Models\Student;
use App\Models\StudentDueAlertState;
use App\Models\StudentNotice;
use App\Models\StudentNoticeAcknowledgement;
use App\Models\TeacherNote;
use App\Models\WeeklyExamMark;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class StudentPortalDashboard extends Component
{
    public ?int $dismissedNoticeId = null;
    public bool $dismissedDueAlertSession = false;

    public function render()
    {
        $student = $this->resolveStudent();
        $routineDate = $this->dashboardRoutineDate();
        if (! $student) {
            return view('livewire.students.student-portal-dashboard', [
                'student' => null,
                'dueMonths' => [],
                'dueMonthCount' => 0,
                'dueAmount' => 0,
                'dueAlertMessage' => null,
                'todayRoutines' => collect(),
                'routineDate' => $routineDate,
                'noteCount' => 0,
                'latestNoteTitle' => null,
                'latestNoteTeacherName' => null,
                'weeklyExamCount' => 0,
                'weeklyAveragePercent' => 0,
                'weeklyLatestExam' => null,
                'weeklyRecentMarks' => collect(),
                'weeklyTrendDelta' => null,
                'weeklyPerformanceLabel' => 'No exam recorded',
                'pendingNotice' => null,
            ]);
        }

        $asOfMonth = now()->startOfMonth();
        $student->ensureInvoicesThroughMonth($asOfMonth);
        if ($currentInvoice = $student->feeInvoices()->where('billing_month', $asOfMonth->toDateString())->first()) {
            $student->adjustInvoiceForAttendance($currentInvoice);
        }

        $notesQuery = TeacherNote::query()
            ->where(function ($q) use ($student) {
                $q->where('class_level', $student->class_level)
                    ->orWhereJsonContains('target_classes', $student->class_level);
            })
            ->where(function ($q) use ($student) {
                $q->where('section', $student->section)
                    ->orWhereJsonContains('target_sections', $student->section);
            });
        $noteCount = (clone $notesQuery)->count();
        $latestNote = (clone $notesQuery)
            ->with('uploader')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
        $latestNoteTitle = $latestNote?->title;
        $latestNoteTeacherName = $latestNote?->uploader?->name;

        $todayRoutines = Routine::query()
            ->with('teacher')
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
            ->whereDate('routine_date', $routineDate)
            ->orderBy('time_slot')
            ->get();

        $weeklyBase = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->orderByDesc('exam_date')
            ->orderByDesc('id');
        $weeklyExamCount = (clone $weeklyBase)->count();
        $weeklyRecentMarks = (clone $weeklyBase)->limit(5)->get();
        $weeklyLatestExam = $weeklyRecentMarks->first();
        $weeklyAveragePercent = round((float) ((clone $weeklyBase)
            ->reorder()
            ->selectRaw('AVG(CASE WHEN max_marks > 0 THEN (marks_obtained / max_marks) * 100 ELSE 0 END) as avg_percent')
            ->value('avg_percent') ?? 0), 2);
        $latestThreeAverage = $this->averagePercentage($weeklyRecentMarks->take(3));
        $previousThreeAverage = $this->averagePercentage((clone $weeklyBase)->skip(3)->limit(3)->get());
        $weeklyTrendDelta = null;
        if ($weeklyExamCount >= 4 && $previousThreeAverage > 0) {
            $weeklyTrendDelta = round($latestThreeAverage - $previousThreeAverage, 2);
        }
        $weeklyPerformanceLabel = $this->weeklyPerformanceLabel($weeklyAveragePercent, $weeklyExamCount);

        $dueInvoices = $student->feeInvoices()
            ->where('billing_month', '<=', now()->endOfMonth()->toDateString())
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->orderBy('billing_month')
            ->get();

        $dueMonths = $dueInvoices
            ->map(fn ($invoice) => optional($invoice->billing_month)?->format('M Y'))
            ->filter()
            ->values()
            ->all();

        $dueMonthCount = count($dueMonths);
        // Keep due amount aligned with dashboard rule: due months * monthly fee
        $dueAmount = round($dueMonthCount * (float) ($student->monthly_fee ?? 0), 2);

        $showDueAlert = false;
        if (Schema::hasTable('student_due_alert_states')) {
            $dueAlertState = StudentDueAlertState::firstOrCreate(
                ['student_id' => $student->id],
                ['force_show_due_alert' => false]
            );

            if ($dueAmount <= 0 && $dueAlertState->force_show_due_alert) {
                $dueAlertState->force_show_due_alert = false;
                $dueAlertState->save();
            }

            $canShowByDateRule = now()->day > 6
                && (
                    $dueAlertState->dismissed_until === null
                    || now()->greaterThanOrEqualTo($dueAlertState->dismissed_until)
                );
            $showDueAlert = $dueAmount > 0 && ($dueAlertState->force_show_due_alert || $canShowByDateRule);
        } else {
            $showDueAlert = now()->day > 6 && $dueAmount > 0;
        }

        if ($this->dismissedDueAlertSession) {
            $showDueAlert = false;
        }

        $dueAlertMessage = null;
        if ($showDueAlert) {
            $monthBangla = $this->toBanglaDigits((string) $dueMonthCount);
            $amountBangla = $this->toBanglaDigits(number_format($dueAmount, 2, '.', ''));
            $dueAlertMessage = "প্রিয় শিক্ষার্থী, আপনার {$monthBangla} মাসের বকেয়া জমা হয়েছে {$amountBangla} টাকা । অনুগ্রহ করে বকেয়াটি আগামী ২ কর্ম দিবসের মধ্যে পরিশোধ করুন ।";
        }

        $pendingNotice = StudentNotice::query()
            ->where('is_active', true)
            ->whereDate('notice_date', '<=', now()->toDateString())
            ->whereDoesntHave('acknowledgements', function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->where('action', 'acknowledged');
            })
            ->orderByDesc('notice_date')
            ->orderByDesc('id')
            ->first();

        if ($pendingNotice && $this->dismissedNoticeId === (int) $pendingNotice->id) {
            $pendingNotice = null;
        }

        return view('livewire.students.student-portal-dashboard', [
            'student' => $student,
            'dueMonths' => $dueMonths,
            'dueMonthCount' => $dueMonthCount,
            'dueAmount' => $dueAmount,
            'dueAlertMessage' => $dueAlertMessage,
            'todayRoutines' => $todayRoutines,
            'routineDate' => $routineDate,
            'noteCount' => $noteCount,
            'latestNoteTitle' => $latestNoteTitle,
            'latestNoteTeacherName' => $latestNoteTeacherName,
            'weeklyExamCount' => $weeklyExamCount,
            'weeklyAveragePercent' => $weeklyAveragePercent,
            'weeklyLatestExam' => $weeklyLatestExam,
            'weeklyRecentMarks' => $weeklyRecentMarks,
            'weeklyTrendDelta' => $weeklyTrendDelta,
            'weeklyPerformanceLabel' => $weeklyPerformanceLabel,
            'pendingNotice' => $pendingNotice,
        ]);
    }

    public function acknowledgeNotice(int $noticeId): void
    {
        $this->markNoticeAction($noticeId, 'acknowledged');
    }

    public function closeNotice(int $noticeId): void
    {
        $this->dismissedNoticeId = $noticeId;
    }

    public function closeDueAlert(): void
    {
        $this->dismissedDueAlertSession = true;

        $student = $this->resolveStudent();
        if (! $student) {
            return;
        }

        if (! Schema::hasTable('student_due_alert_states')) {
            return;
        }

        StudentDueAlertState::updateOrCreate(
            ['student_id' => $student->id],
            [
                'dismissed_until' => now()->addDays(3),
                'force_show_due_alert' => false,
            ]
        );
    }

    protected function resolveStudent(): ?Student
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (! empty($user->studentProfile)) {
            return $user->studentProfile;
        }

        if (! empty($user->contact_number)) {
            $student = Student::where('phone_number', $user->contact_number)->first();
            if ($student) {
                return $student;
            }
        }

        return null;
    }

    protected function markNoticeAction(int $noticeId, string $action): void
    {
        $student = $this->resolveStudent();
        if (! $student) {
            return;
        }

        $notice = StudentNotice::query()
            ->whereKey($noticeId)
            ->where('is_active', true)
            ->first();

        if (! $notice) {
            return;
        }

        StudentNoticeAcknowledgement::updateOrCreate(
            [
                'student_notice_id' => $notice->id,
                'student_id' => $student->id,
            ],
            [
                'action' => $action,
                'acknowledged_at' => now(),
            ]
        );
    }

    protected function toBanglaDigits(string $value): string
    {
        return strtr($value, [
            '0' => '০',
            '1' => '১',
            '2' => '২',
            '3' => '৩',
            '4' => '৪',
            '5' => '৫',
            '6' => '৬',
            '7' => '৭',
            '8' => '৮',
            '9' => '৯',
            '.' => '.',
            ',' => ',',
        ]);
    }

    protected function averagePercentage($marks): float
    {
        $rows = collect($marks);
        if ($rows->isEmpty()) {
            return 0;
        }

        $average = $rows->avg(function ($row) {
            $max = (float) ($row->max_marks ?? 0);
            if ($max <= 0) {
                return 0;
            }

            return ((float) ($row->marks_obtained ?? 0) / $max) * 100;
        });

        return round((float) $average, 2);
    }

    protected function weeklyPerformanceLabel(float $averagePercent, int $examCount): string
    {
        if ($examCount <= 0) {
            return 'No exam recorded';
        }

        if ($averagePercent >= 80) {
            return 'Excellent';
        }

        if ($averagePercent >= 65) {
            return 'Good';
        }

        if ($averagePercent >= 50) {
            return 'Needs Focus';
        }

        return 'Needs Improvement';
    }

    protected function dashboardRoutineDate(): string
    {
        $now = now('Asia/Dhaka');
        $cutoff = $now->copy()->setTime(19, 0);

        return $now->greaterThan($cutoff)
            ? $now->copy()->addDay()->toDateString()
            : $now->toDateString();
    }
}
