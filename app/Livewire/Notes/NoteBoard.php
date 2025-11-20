<?php

namespace App\Livewire\Notes;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentNote;
use App\Support\AcademyOptions;
use Livewire\Component;
use Livewire\WithPagination;

class NoteBoard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dateFilter = '';
    public string $classFilter = 'all';
    public string $sectionFilter = 'all';
    public string $absenceDate = '';
    public string $absenceClass = 'all';
    public string $absenceSection = 'all';
    public ?int $activeAttendanceId = null;
    public ?string $activeStudentName = null;

    public array $form = [
        'student_id' => '',
        'note_date' => '',
        'category' => '',
        'body' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.student_id' => ['required', 'exists:students,id'],
            'form.note_date' => ['required', 'date'],
            'form.category' => ['required', 'string', 'max:50'],
            'form.body' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->form['note_date'] = now()->format('Y-m-d');
        $this->absenceDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $notes = StudentNote::query()
            ->with('student')
            ->when($this->search, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%' . $this->search . '%')))
            ->when($this->dateFilter, fn ($q) => $q->whereDate('note_date', $this->dateFilter))
            ->when($this->classFilter !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_level', $this->classFilter)))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section', $this->sectionFilter)))
            ->latest('note_date')
            ->paginate(10);

        $absentStudents = Attendance::query()
            ->with('student')
            ->where('status', 'absent')
            ->whereDoesntHave('linkedNote')
            ->when($this->absenceDate, fn ($q) => $q->whereDate('attendance_date', $this->absenceDate))
            ->when($this->absenceClass !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('class_level', $this->absenceClass)))
            ->when($this->absenceSection !== 'all', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('section', $this->absenceSection)))
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->sortBy(fn ($record) => $record->student->name ?? '');

        return view('livewire.notes.note-board', [
            'notes' => $notes,
            'categories' => AcademyOptions::absenceCategories(),
            'classOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
            'absentStudents' => $absentStudents,
            'absentCount' => $absentStudents->count(),
        ]);
    }

    public function saveNote(): void
    {
        $data = $this->validate()['form'];

        StudentNote::create([
            'student_id' => $data['student_id'],
            'note_date' => $data['note_date'],
            'category' => $data['category'],
            'body' => $data['body'],
            'attendance_id' => $this->activeAttendanceId,
            'created_by' => auth()->id(),
        ]);

        $this->resetForm();
        $this->activeAttendanceId = null;
    }

    public function selectAbsentStudent(int $attendanceId): void
    {
        $attendance = Attendance::with('student')->findOrFail($attendanceId);
        $this->form['student_id'] = (string) $attendance->student_id;
        $this->form['note_date'] = optional($attendance->attendance_date)->format('Y-m-d');
        $this->form['category'] = $attendance->category ?? '';
        $this->form['body'] = $attendance->note ?? '';
        $this->activeAttendanceId = $attendance->id;
        $this->activeStudentName = $attendance->student?->name;
    }

    public function delete(int $noteId): void
    {
        StudentNote::where('id', $noteId)->delete();
    }

    public function cancelNote(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->form = [
            'student_id' => '',
            'note_date' => now()->format('Y-m-d'),
            'category' => '',
            'body' => '',
        ];
        $this->activeAttendanceId = null;
        $this->activeStudentName = null;
    }
}
