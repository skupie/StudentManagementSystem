<?php

namespace App\Livewire\Notes;

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

        return view('livewire.notes.note-board', [
            'notes' => $notes,
            'students' => Student::orderBy('name')->get(),
            'categories' => AcademyOptions::absenceCategories(),
            'classOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
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
            'created_by' => auth()->id(),
        ]);

        $this->resetForm();
    }

    public function delete(int $noteId): void
    {
        StudentNote::where('id', $noteId)->delete();
    }

    protected function resetForm(): void
    {
        $this->form = [
            'student_id' => '',
            'note_date' => now()->format('Y-m-d'),
            'category' => '',
            'body' => '',
        ];
    }
}
