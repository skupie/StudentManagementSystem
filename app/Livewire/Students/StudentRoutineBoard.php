<?php

namespace App\Livewire\Students;

use App\Models\Routine;
use App\Models\Student;
use App\Models\WeeklyExamAssignment;
use App\Models\WeeklyExamSyllabus;
use Carbon\Carbon;
use Livewire\Component;

class StudentRoutineBoard extends Component
{
    public string $view = 'weekly';
    public string $weekStart = 'all';
    public string $classDate = '';

    protected $queryString = [
        'view' => ['except' => 'weekly'],
        'weekStart' => ['except' => 'all'],
        'classDate' => ['except' => ''],
    ];

    public function mount(): void
    {
        $now = now('Asia/Dhaka');
        $cutoff = $now->copy()->setTime(19, 0);
        $this->classDate = $now->greaterThan($cutoff)
            ? $now->copy()->addDay()->toDateString()
            : $now->toDateString();
    }

    public function render()
    {
        $student = $this->resolveStudent();
        if (! $student) {
            return view('livewire.students.student-routine-board', [
                'student' => null,
                'weeklyAssignments' => collect(),
                'weeklySyllabi' => collect(),
                'routines' => collect(),
                'weeklyWeekOptions' => collect(),
            ]);
        }

        $weeklyBase = WeeklyExamAssignment::query()
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
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

        if ($this->weekStart !== 'all' && ! $weeklyWeekOptions->pluck('key')->contains($this->weekStart)) {
            $this->weekStart = 'all';
        }

        $weeklyAssignments = WeeklyExamAssignment::query()
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
            ->when($this->weekStart !== 'all', function ($q) {
                $start = Carbon::parse($this->weekStart)->startOfWeek()->toDateString();
                $end = Carbon::parse($start)->endOfWeek()->toDateString();
                $q->whereBetween('exam_date', [$start, $end]);
            })
            ->orderByDesc('exam_date')
            ->orderBy('subject')
            ->get();

        $weeklySyllabi = WeeklyExamSyllabus::query()
            ->with('creator')
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
            ->when($this->weekStart !== 'all', function ($q) {
                $start = Carbon::parse($this->weekStart)->startOfWeek()->toDateString();
                $end = Carbon::parse($start)->endOfWeek()->toDateString();
                $q->whereBetween('week_start_date', [$start, $end]);
            })
            ->orderByDesc('week_start_date')
            ->orderBy('subject')
            ->get();

        $routines = Routine::query()
            ->with('teacher')
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
            ->when($this->classDate !== '', fn ($q) => $q->whereDate('routine_date', $this->classDate))
            ->orderBy('time_slot')
            ->get();

        $classDateOptions = Routine::query()
            ->where('class_level', $student->class_level)
            ->where('section', $student->section)
            ->whereNotNull('routine_date')
            ->distinct()
            ->orderByDesc('routine_date')
            ->pluck('routine_date')
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->toDateString())
            ->values();

        if ($this->classDate !== '' && ! $classDateOptions->contains($this->classDate)) {
            // Keep selected date even when there are currently no rows for that date.
            $classDateOptions = $classDateOptions->prepend($this->classDate)->unique()->values();
        }

        return view('livewire.students.student-routine-board', [
            'student' => $student,
            'weeklyAssignments' => $weeklyAssignments,
            'weeklySyllabi' => $weeklySyllabi,
            'routines' => $routines,
            'weeklyWeekOptions' => $weeklyWeekOptions,
            'classDateOptions' => $classDateOptions,
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
