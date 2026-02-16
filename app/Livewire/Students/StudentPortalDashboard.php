<?php

namespace App\Livewire\Students;

use App\Models\Routine;
use App\Models\Student;
use App\Models\TeacherNote;
use Livewire\Component;

class StudentPortalDashboard extends Component
{
    public function render()
    {
        $student = $this->resolveStudent();
        if (! $student) {
            return view('livewire.students.student-portal-dashboard', [
                'student' => null,
                'dueMonths' => [],
                'dueMonthCount' => 0,
                'dueAmount' => 0,
                'todayRoutines' => collect(),
                'noteCount' => 0,
                'latestNoteTitle' => null,
                'latestNoteTeacherName' => null,
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
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
            ->whereDate('routine_date', now()->toDateString())
            ->orderBy('time_slot')
            ->get();

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
        $dueAmount = round($dueMonthCount * (float) ($student->monthly_fee ?? 0), 2);

        return view('livewire.students.student-portal-dashboard', [
            'student' => $student,
            'dueMonths' => $dueMonths,
            'dueMonthCount' => $dueMonthCount,
            'dueAmount' => $dueAmount,
            'todayRoutines' => $todayRoutines,
            'noteCount' => $noteCount,
            'latestNoteTitle' => $latestNoteTitle,
            'latestNoteTeacherName' => $latestNoteTeacherName,
        ]);
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
}
