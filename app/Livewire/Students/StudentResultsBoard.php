<?php

namespace App\Livewire\Students;

use App\Models\ModelTest;
use App\Models\ModelTestResult;
use App\Models\ModelTestStudent;
use App\Models\Student;
use App\Models\WeeklyExamMark;
use Carbon\Carbon;
use Livewire\Component;

class StudentResultsBoard extends Component
{
    public string $weeklyWeek = 'all';
    public string $modelExam = 'all';

    public function updatedWeeklyWeek(): void
    {
        // no-op, live update
    }

    public function updatedModelExam(): void
    {
        // no-op, live update
    }

    public function render()
    {
        $student = $this->resolveStudent();
        if (! $student) {
            return view('livewire.students.student-results-board', [
                'student' => null,
                'weeklyWeekOptions' => [],
                'weeklyResults' => collect(),
                'modelExamOptions' => collect(),
                'modelResults' => collect(),
            ]);
        }

        $weeklyBase = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->orderByDesc('exam_date');

        $weeklyRows = (clone $weeklyBase)->get();
        $weeklyWeekOptions = $weeklyRows
            ->map(function ($row) {
                $start = Carbon::parse($row->exam_date)->startOfWeek()->toDateString();
                $end = Carbon::parse($start)->endOfWeek()->toDateString();
                return [
                    'key' => $start,
                    'label' => Carbon::parse($start)->format('d M Y') . ' - ' . Carbon::parse($end)->format('d M Y'),
                ];
            })
            ->unique('key')
            ->values();

        if ($this->weeklyWeek !== 'all' && ! $weeklyWeekOptions->pluck('key')->contains($this->weeklyWeek)) {
            $this->weeklyWeek = 'all';
        }

        $weeklyResults = WeeklyExamMark::query()
            ->where('student_id', $student->id)
            ->when($this->weeklyWeek !== 'all', function ($q) {
                $start = Carbon::parse($this->weeklyWeek)->startOfWeek()->toDateString();
                $end = Carbon::parse($start)->endOfWeek()->toDateString();
                $q->whereBetween('exam_date', [$start, $end]);
            })
            ->latest('exam_date')
            ->get();

        $modelStudentIds = $this->resolveModelTestStudentIds($student);
        $modelExamOptions = collect();
        $modelResults = collect();

        if (! empty($modelStudentIds)) {
            $examIds = ModelTestResult::query()
                ->whereIn('model_test_student_id', $modelStudentIds)
                ->distinct()
                ->pluck('model_test_id')
                ->filter()
                ->values();

            $modelExamOptions = ModelTest::query()
                ->whereIn('id', $examIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($this->modelExam !== 'all' && ! $modelExamOptions->pluck('id')->map(fn ($v) => (string) $v)->contains($this->modelExam)) {
                $this->modelExam = 'all';
            }

            $modelResults = ModelTestResult::query()
                ->with('test')
                ->whereIn('model_test_student_id', $modelStudentIds)
                ->when($this->modelExam !== 'all', fn ($q) => $q->where('model_test_id', (int) $this->modelExam))
                ->orderByDesc('year')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('livewire.students.student-results-board', [
            'student' => $student,
            'weeklyWeekOptions' => $weeklyWeekOptions,
            'weeklyResults' => $weeklyResults,
            'modelExamOptions' => $modelExamOptions,
            'modelResults' => $modelResults,
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

    protected function resolveModelTestStudentIds(Student $student): array
    {
        $query = ModelTestStudent::query()
            ->where(function ($q) use ($student) {
                if (! empty($student->phone_number)) {
                    $q->orWhere('contact_number', $student->phone_number);
                }
                $q->orWhere(function ($inner) use ($student) {
                    $inner->where('name', $student->name)
                        ->where('section', $student->section);
                });
            });

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }
}
