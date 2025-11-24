<?php

namespace App\Livewire\Exams;

use App\Models\Student;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WeeklyExamBoard extends Component
{
    use WithPagination;

    public string $classFilter = 'hsc_1';
    public string $sectionFilter = 'science';
    public string $subjectFilter = 'all';
    public string $examDateFilter = '';
    public string $search = '';

    public array $form = [
        'student_id' => '',
        'class_level' => 'hsc_1',
        'section' => 'science',
        'subject' => '',
        'exam_date' => '',
        'marks_obtained' => '',
        'max_marks' => 30,
        'remarks' => '',
    ];

    public ?int $editingId = null;

    protected function rules(): array
    {
        return [
            'form.student_id' => ['required', 'exists:students,id'],
            'form.class_level' => ['required', 'in:' . implode(',', array_keys(AcademyOptions::classes()))],
            'form.section' => ['required', 'in:' . implode(',', array_keys(AcademyOptions::sections()))],
            'form.subject' => [
                'required',
                Rule::in(array_keys(AcademyOptions::subjectsForSection($this->form['section'] ?? $this->sectionFilter))),
            ],
            'form.exam_date' => ['required', 'date'],
            'form.marks_obtained' => ['required', 'integer', 'min:0'],
            'form.max_marks' => ['required', 'integer', 'min:1'],
            'form.remarks' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->form['exam_date'] = now()->format('Y-m-d');
        $this->form['subject'] = $this->defaultSubjectForSection($this->sectionFilter);
    }

    public function render()
    {
        $formClass = $this->form['class_level'] ?: $this->classFilter;
        $formSection = $this->form['section'] ?: $this->sectionFilter;

        $students = Student::query()
            ->where('status', 'active')
            ->where('class_level', $formClass)
            ->where('section', $formSection)
            ->orderBy('name')
            ->get();

        $availableSubjects = AcademyOptions::subjectsForSection($this->sectionFilter);
        if ($this->subjectFilter !== 'all' && ! array_key_exists($this->subjectFilter, $availableSubjects)) {
            $this->subjectFilter = 'all';
        }

        $marks = WeeklyExamMark::query()
            ->with('student')
            ->when($this->search, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%' . $this->search . '%')))
            ->where('class_level', $this->classFilter)
            ->where('section', $this->sectionFilter)
            ->when($this->subjectFilter !== 'all', fn ($q) => $q->where('subject', $this->subjectFilter))
            ->when($this->examDateFilter, fn ($q) => $q->whereDate('exam_date', $this->examDateFilter))
            ->latest('exam_date')
            ->paginate(10);

        return view('livewire.exams.weekly-exam-board', [
            'students' => $students,
            'marks' => $marks,
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => AcademyOptions::sections(),
            'subjectOptions' => ['all' => 'All Subjects'] + $availableSubjects,
            'subjectList' => AcademyOptions::subjectsForSection($formSection),
        ]);
    }

    public function updatedFormStudentId($value): void
    {
        $student = Student::find($value);
        if ($student) {
            $this->form['class_level'] = $student->class_level;
            $this->form['section'] = $student->section;
            $this->ensureFormSubject();
        }
    }

    public function save(): void
    {
        $data = $this->validate()['form'];
        $data['recorded_by'] = auth()->id();

        WeeklyExamMark::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetForm();
    }

    public function edit(int $markId): void
    {
        $mark = WeeklyExamMark::findOrFail($markId);
        $this->editingId = $mark->id;
        $this->form = [
            'student_id' => $mark->student_id,
            'class_level' => $mark->class_level,
            'section' => $mark->section,
            'subject' => $mark->subject,
            'exam_date' => $mark->exam_date->format('Y-m-d'),
            'marks_obtained' => $mark->marks_obtained,
            'max_marks' => $mark->max_marks,
            'remarks' => $mark->remarks,
        ];
        $this->ensureFormSubject();
    }

    public function delete(int $markId): void
    {
        WeeklyExamMark::where('id', $markId)->delete();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $lastClass = $this->form['class_level'] ?: $this->classFilter;
        $lastSection = $this->form['section'] ?: $this->sectionFilter;
        $lastDate = $this->form['exam_date'] ?: now()->format('Y-m-d');
        $preferredSubject = $this->form['subject'] ?: ($this->subjectFilter !== 'all' ? $this->subjectFilter : '');
        $subject = $preferredSubject ?: $this->defaultSubjectForSection($lastSection);

        $this->form = [
            'student_id' => '',
            'class_level' => $lastClass,
            'section' => $lastSection,
            'subject' => $subject,
            'exam_date' => $lastDate,
            'marks_obtained' => '',
            'max_marks' => 30,
            'remarks' => '',
        ];
        $this->ensureFormSubject();
    }

    public function exportPdf()
    {
        return redirect()->route('reports.weekly-exams.pdf', [
            'class' => $this->classFilter,
            'section' => $this->sectionFilter,
            'date' => $this->examDateFilter,
            'subject' => $this->subjectFilter,
        ]);
    }

    public function updatedSectionFilter(): void
    {
        $this->subjectFilter = 'all';
    }

    public function updatedFormSection(): void
    {
        $this->form['student_id'] = '';
        $this->ensureFormSubject();
    }

    public function updatedFormClassLevel(): void
    {
        $this->form['student_id'] = '';
    }

    protected function defaultSubjectForSection(?string $section): string
    {
        return array_key_first(AcademyOptions::subjectsForSection($section)) ?? '';
    }

    protected function ensureFormSubject(): void
    {
        $section = $this->form['section'] ?: $this->sectionFilter;
        $available = AcademyOptions::subjectsForSection($section);
        if (! array_key_exists($this->form['subject'] ?? '', $available)) {
            $this->form['subject'] = array_key_first($available) ?? '';
        }
    }
}
