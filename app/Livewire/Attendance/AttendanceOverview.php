<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Support\AcademyOptions;
use Carbon\Carbon;
use Livewire\Component;

class AttendanceOverview extends Component
{
    public string $filterDate;
    public string $filterClass = 'all';
    public string $filterSection = 'all';

    public function mount(): void
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $date = $this->parsedDate();

        $query = Attendance::query()
            ->with(['student', 'linkedNote'])
            ->whereDate('attendance_date', $date->toDateString())
            ->when($this->filterClass !== 'all', function ($builder) {
                $builder->whereHas('student', fn ($sub) => $sub->where('class_level', $this->filterClass));
            })
            ->when($this->filterSection !== 'all', function ($builder) {
                $builder->whereHas('student', fn ($sub) => $sub->where('section', $this->filterSection));
            });

        $records = $query->get()->sortBy(function ($record) {
            return $record->student?->name ?? '';
        });

        $present = $records->where('status', 'present');
        $absent = $records->where('status', 'absent')->values();

        $totals = [
            'present' => $present->count(),
            'absent' => $absent->count(),
        ];

        return view('livewire.attendance.attendance-overview', [
            'records' => $records,
            'absentRecords' => $absent,
            'totals' => $totals,
            'dateLabel' => $date->format('l, d M Y'),
            'classOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
        ]);
    }

    protected function parsedDate(): Carbon
    {
        try {
            return Carbon::parse($this->filterDate)->startOfDay();
        } catch (\Throwable $e) {
            return now()->startOfDay();
        }
    }
}
