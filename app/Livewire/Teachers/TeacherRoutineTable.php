<?php

namespace App\Livewire\Teachers;

use App\Models\Routine;
use App\Models\Teacher;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class TeacherRoutineTable extends Component
{
    public string $viewDate = '';

    public function mount(): void
    {
        $now = now('Asia/Dhaka');
        $cutoff = $now->copy()->setTime(20, 0);
        $this->viewDate = $now->greaterThan($cutoff)
            ? $now->copy()->addDay()->toDateString()
            : $now->toDateString();
    }

    public function render()
    {
        $teacher = $this->resolveTeacher();

        $rows = collect();
        if ($teacher) {
            $rows = Routine::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('routine_date', $this->viewDate)
                ->orderBy('time_slot')
                ->get()
                ->map(function (Routine $row) {
                    return [
                        'class_label' => AcademyOptions::classLabel($row->class_level),
                        'section_label' => AcademyOptions::sectionLabel($row->section),
                        'subject' => $row->subject,
                        'time_slot' => $row->time_slot,
                    ];
                });
        }

        return view('livewire.teachers.teacher-routine-table', [
            'teacher' => $teacher,
            'rows' => $rows,
            'viewDate' => $this->viewDate,
        ]);
    }

    protected function resolveTeacher(): ?Teacher
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (Schema::hasColumn('teachers', 'user_id')) {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                return $teacher;
            }
        }

        if (! empty($user->contact_number)) {
            $teacher = Teacher::where('contact_number', $user->contact_number)->first();
            if ($teacher) {
                return $teacher;
            }
        }

        return Teacher::where('name', $user->name)->first();
    }
}

