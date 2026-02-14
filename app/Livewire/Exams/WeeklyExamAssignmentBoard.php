<?php

namespace App\Livewire\Exams;

use App\Models\Teacher;
use App\Models\WeeklyExamAssignment;
use App\Support\AcademyOptions;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WeeklyExamAssignmentBoard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dateFilter = '';
    public string $teacherFilter = 'all';

    public array $form = [
        'exam_date' => '',
        'exam_name' => '',
        'class_level' => 'hsc_1',
        'section' => 'science',
        'subject' => '',
        'teacher_id' => '',
    ];

    public function mount(): void
    {
        $this->form['exam_date'] = now('Asia/Dhaka')->toDateString();
        $this->ensureSubject();
    }

    protected function rules(): array
    {
        return [
            'form.exam_date' => ['required', 'date'],
            'form.exam_name' => ['required', 'string', 'max:255'],
            'form.class_level' => ['required', Rule::in(array_keys(AcademyOptions::classes()))],
            'form.section' => ['required', Rule::in(array_keys(AcademyOptions::sections()))],
            'form.subject' => ['required', Rule::in(array_keys(AcademyOptions::subjectsForSection($this->form['section'] ?? 'science')))],
            'form.teacher_id' => ['required', 'exists:teachers,id'],
        ];
    }

    public function render()
    {
        $teachers = Teacher::query()->where('is_active', true)->orderBy('name')->get();

        $assignments = WeeklyExamAssignment::query()
            ->with('teacher')
            ->when($this->search, fn ($q) => $q->where('exam_name', 'like', '%' . $this->search . '%'))
            ->when($this->dateFilter, fn ($q) => $q->whereDate('exam_date', $this->dateFilter))
            ->when($this->teacherFilter !== 'all', fn ($q) => $q->where('teacher_id', $this->teacherFilter))
            ->orderByDesc('exam_date')
            ->orderBy('exam_name')
            ->paginate(12);

        return view('livewire.exams.weekly-exam-assignment-board', [
            'teachers' => $teachers,
            'assignments' => $assignments,
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => AcademyOptions::sections(),
            'subjectOptions' => AcademyOptions::subjectsForSection($this->form['section'] ?? 'science'),
        ]);
    }

    public function save(): void
    {
        $data = $this->validate()['form'];

        WeeklyExamAssignment::create([
            'exam_date' => $data['exam_date'],
            'exam_name' => $data['exam_name'],
            'class_level' => $data['class_level'],
            'section' => $data['section'],
            'subject' => $data['subject'],
            'teacher_id' => $data['teacher_id'],
            'created_by' => auth()->id(),
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: 'Weekly exam assignment created.');
    }

    public function delete(int $id): void
    {
        WeeklyExamAssignment::whereKey($id)->delete();
        $this->dispatch('notify', message: 'Assignment removed.');
    }

    public function updatedFormSection(): void
    {
        $this->ensureSubject();
    }

    protected function resetForm(): void
    {
        $this->form['exam_name'] = '';
        $this->form['teacher_id'] = '';
        $this->form['exam_date'] = now('Asia/Dhaka')->toDateString();
        $this->ensureSubject();
        $this->resetValidation();
    }

    protected function ensureSubject(): void
    {
        $available = AcademyOptions::subjectsForSection($this->form['section'] ?? 'science');
        if (! array_key_exists($this->form['subject'] ?? '', $available)) {
            $this->form['subject'] = array_key_first($available) ?? '';
        }
    }
}
