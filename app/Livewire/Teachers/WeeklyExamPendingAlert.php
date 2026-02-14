<?php

namespace App\Livewire\Teachers;

use App\Models\Teacher;
use App\Models\WeeklyExamAssignment;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class WeeklyExamPendingAlert extends Component
{
    public string $viewDate = '';

    public function mount(): void
    {
        $this->viewDate = now('Asia/Dhaka')->toDateString();
    }

    public function render()
    {
        $user = auth()->user();
        $teacher = $this->resolveTeacher();

        $pendingRows = collect();
        $completedCount = 0;

        if ($teacher && $user) {
            $assignmentRows = WeeklyExamAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->whereDate('exam_date', '<=', $this->viewDate)
                ->orderBy('exam_date')
                ->orderBy('exam_name')
                ->get();

            // Alert should clear once teacher has entered marks for any student.
            $hasAnyStudentMark = WeeklyExamMark::query()
                ->where('recorded_by', $user->id)
                ->exists();

            $mapped = $assignmentRows->map(function (WeeklyExamAssignment $assignment) {
                return [
                    'exam_date' => optional($assignment->exam_date)->format('Y-m-d'),
                    'exam_name' => $assignment->exam_name,
                    'subject' => AcademyOptions::subjectLabel($assignment->subject),
                    'class_label' => AcademyOptions::classLabel($assignment->class_level),
                    'section_label' => AcademyOptions::sectionLabel($assignment->section),
                ];
            });

            if ($hasAnyStudentMark) {
                $pendingRows = collect();
                $completedCount = $assignmentRows->count();
            } else {
                $pendingRows = $mapped->values();
                $completedCount = 0;
            }
        }

        return view('livewire.teachers.weekly-exam-pending-alert', [
            'teacher' => $teacher,
            'pendingRows' => $pendingRows,
            'completedCount' => $completedCount,
            'totalScheduled' => $pendingRows->count() + $completedCount,
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
