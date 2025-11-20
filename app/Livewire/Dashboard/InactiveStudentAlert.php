<?php

namespace App\Livewire\Dashboard;

use App\Models\Student;
use App\Support\AcademyOptions;
use Carbon\Carbon;
use Livewire\Component;

class InactiveStudentAlert extends Component
{
    public string $classFilter = 'all';
    public string $sectionFilter = 'all';

    public function render()
    {
        $now = now();
        $referenceStart = $now->day > 10
            ? $now->copy()->startOfMonth()
            : $now->copy()->subMonth()->startOfMonth();
        $referenceEnd = $referenceStart->copy()->endOfMonth();
        $attendanceThreshold = 10;

        $inactiveStudents = Student::query()
            ->where('status', 'inactive')
            ->when($this->classFilter !== 'all', fn ($q) => $q->where('class_level', $this->classFilter))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->orderBy('name')
            ->take(10)
            ->get()
            ->map(fn ($student) => [
                'student' => $student,
                'reason' => 'Inactive status',
            ])
            ->values()
            ->toBase();

        $limitedAttendanceStudents = Student::query()
            ->where('status', 'active')
            ->when($this->classFilter !== 'all', fn ($q) => $q->where('class_level', $this->classFilter))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->withCount(['attendances as present_count' => function ($query) use ($referenceStart, $referenceEnd) {
                $query->where('status', 'present')
                    ->whereBetween('attendance_date', [$referenceStart, $referenceEnd]);
            }])
            ->having('present_count', '<=', $attendanceThreshold)
            ->orderBy('name')
            ->take(10)
            ->get()
            ->map(function ($student) {
                $count = (int) ($student->present_count ?? 0);
                return [
                    'student' => $student,
                    'reason' => $count > 0
                        ? "Only {$count} days present during the period"
                        : 'No attendance recorded during the period',
                ];
            })
            ->values()
            ->toBase();

        $records = $inactiveStudents
            ->merge($limitedAttendanceStudents)
            ->unique(fn ($item) => $item['student']->id)
            ->take(10);

        return view('livewire.dashboard.inactive-student-alert', [
            'records' => $records,
            'referenceLabel' => $referenceStart->format('M Y'),
            'classOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
        ]);
    }
}
