<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\StudentNote;
use App\Support\AcademyOptions;
use Carbon\Carbon;
use Livewire\Component;

class AttendanceBoard extends Component
{
    public string $selectedClass = 'hsc_1';
    public string $selectedSection = 'science';
    public string $attendanceDate;
    public string $search = '';

    public ?int $noteStudentId = null;
    public string $noteCategory = '';
    public string $noteBody = '';

    public function mount(): void
    {
        $this->attendanceDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $isWeekend = $this->isWeekend();
        $isHoliday = $this->isHoliday();

        $attendanceYear = Carbon::parse($this->attendanceDate)->year;
        $allowedAcademicYears = [
            ($attendanceYear - 1) . '-' . $attendanceYear,
            $attendanceYear . '-' . ($attendanceYear + 1),
        ];

        $students = Student::query()
            ->where('status', 'active')
            ->where('is_passed', false)
            ->where('class_level', $this->selectedClass)
            ->where('section', $this->selectedSection)
            ->whereDate('enrollment_date', '<=', $this->attendanceDate)
            ->whereIn('academic_year', $allowedAcademicYears)
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();

        $records = Attendance::query()
            ->whereDate('attendance_date', $this->attendanceDate)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $previousDate = Carbon::parse($this->attendanceDate)->copy()->subDay();
        $previousAbsences = Attendance::query()
            ->whereDate('attendance_date', $previousDate)
            ->where('status', 'absent')
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return view('livewire.attendance.attendance-board', [
            'students' => $students,
            'records' => $records,
            'previousAbsences' => $previousAbsences,
            'previousDateLabel' => $previousDate->format('d M Y'),
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => AcademyOptions::sections(),
            'absenceCategories' => AcademyOptions::absenceCategories(),
            'isWeekend' => $isWeekend,
            'isHoliday' => $isHoliday,
        ]);
    }

    public function markAttendance(int $studentId, string $status): void
    {
        abort_if(! in_array($status, ['present', 'absent'], true), 422);
        if ($this->isWeekend() || $this->isHoliday()) {
            return;
        }

        $record = Attendance::updateOrCreate(
            [
                'student_id' => $studentId,
                'attendance_date' => $this->attendanceDate,
            ],
            [
                'status' => $status,
                'recorded_by' => auth()->id(),
                'category' => $status === 'absent' ? $this->noteCategory : null,
                'note' => $status === 'absent' ? $this->noteBody : null,
            ]
        );

        if ($status === 'absent') {
            $this->noteStudentId = $studentId;
        } else {
            $this->noteStudentId = null;
            $this->noteBody = '';
            $this->noteCategory = '';
        }

        if ($record->status === 'absent' && $this->noteBody) {
            $this->storeNote($record);
        }
    }

    public function openNoteForm(int $studentId): void
    {
        $this->noteStudentId = $studentId;
    }

    public function saveNote(): void
    {
        if ($this->isWeekend() || $this->isHoliday()) {
            return;
        }

        if (! $this->noteStudentId) {
            return;
        }

        $attendance = Attendance::firstOrCreate(
            [
                'student_id' => $this->noteStudentId,
                'attendance_date' => $this->attendanceDate,
            ],
            [
                'status' => 'absent',
                'recorded_by' => auth()->id(),
            ]
        );

        $attendance->update([
            'category' => $this->noteCategory ?: 'Other',
            'note' => $this->noteBody,
        ]);

        $this->storeNote($attendance);

        $this->noteStudentId = null;
        $this->noteBody = '';
        $this->noteCategory = '';
    }

    protected function storeNote(Attendance $attendance): void
    {
        StudentNote::updateOrCreate(
            [
                'attendance_id' => $attendance->id,
            ],
            [
                'student_id' => $attendance->student_id,
                'note_date' => Carbon::parse($this->attendanceDate),
                'category' => $this->noteCategory ?: ($attendance->category ?? 'Other'),
                'body' => $this->noteBody ?: $attendance->note,
                'created_by' => auth()->id(),
            ]
        );
    }

    protected function isWeekend(): bool
    {
        return Carbon::parse($this->attendanceDate)->isFriday();
    }

    protected function isHoliday(): bool
    {
        return Holiday::whereDate('holiday_date', $this->attendanceDate)->exists();
    }
}
