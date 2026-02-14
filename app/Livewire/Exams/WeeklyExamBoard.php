<?php

namespace App\Livewire\Exams;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\WeeklyExamMark;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Schema;
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
    public ?int $confirmingDeleteId = null;
    public string $confirmingDeleteName = '';

    protected function rules(): array
    {
        $section = $this->form['section'] ?: $this->sectionFilter;
        $sectionSubjects = array_keys($this->availableSubjectOptionsForSection($section));
        $sectionKeys = array_keys($this->availableSectionOptions());

        return [
            'form.student_id' => ['required', 'exists:students,id'],
            'form.class_level' => ['required', 'in:' . implode(',', array_keys(AcademyOptions::classes()))],
            'form.section' => ['required', Rule::in($sectionKeys)],
            'form.subject' => [
                'required',
                Rule::in($sectionSubjects),
            ],
            'form.exam_date' => ['required', 'date'],
            'form.marks_obtained' => ['required', 'numeric', 'min:0', 'lte:form.max_marks'],
            'form.max_marks' => ['required', 'integer', 'min:1'],
            'form.remarks' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->normalizeTeacherFilters();
        $this->form['exam_date'] = now()->format('Y-m-d');
        $this->form['subject'] = $this->defaultSubjectForSection($this->sectionFilter);
    }

    public function render()
    {
        $user = auth()->user();
        $isTeacherRole = $this->isTeacherRole();
        $allowedSubjects = $this->teacherAllowedSubjectKeys();
        $sectionOptions = $this->availableSectionOptions();

        if ($this->form['section'] && ! array_key_exists($this->form['section'], $sectionOptions)) {
            $this->form['section'] = (string) (array_key_first($sectionOptions) ?? '');
        }

        if ($this->sectionFilter && ! array_key_exists($this->sectionFilter, $sectionOptions)) {
            $this->sectionFilter = (string) (array_key_first($sectionOptions) ?? '');
        }

        $formClass = $this->form['class_level'] ?: $this->classFilter;
        $formSection = $this->form['section'] ?: $this->sectionFilter;

        $students = Student::query()
            ->where('status', 'active')
            ->where('is_passed', false)
            ->where('class_level', $formClass)
            ->when($isTeacherRole, fn ($q) => $q->whereIn('section', array_keys($sectionOptions)))
            ->where('section', $formSection)
            ->orderBy('name')
            ->get();

        $availableSubjects = $this->availableSubjectOptionsForSection($this->sectionFilter);
        if ($this->subjectFilter !== 'all' && ! array_key_exists($this->subjectFilter, $availableSubjects)) {
            $this->subjectFilter = 'all';
        }

        $marks = WeeklyExamMark::query()
            ->with('student')
            ->when($isTeacherRole, fn ($q) => $q->where('recorded_by', $user?->id))
            ->when($isTeacherRole && ! empty($allowedSubjects), fn ($q) => $q->whereIn('subject', $allowedSubjects))
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
            'sectionOptions' => $sectionOptions,
            'subjectOptions' => ['all' => 'All Subjects'] + $availableSubjects,
            'subjectList' => $this->availableSubjectOptionsForSection($formSection),
            'isTeacherRole' => $isTeacherRole,
            'teacherHasAllowedSubjects' => ! empty($allowedSubjects),
        ]);
    }

    public function updatedFormStudentId($value): void
    {
        $student = Student::find($value);
        if ($student) {
            if ($this->isTeacherRole() && ! in_array((string) $student->section, $this->teacherAllowedSectionKeys(), true)) {
                $this->form['student_id'] = '';
                $this->addError('form.student_id', 'You can only select students from your allowed sections.');
                return;
            }
            $this->form['class_level'] = $student->class_level;
            $this->form['section'] = $student->section;
            $this->ensureFormSubject();
        }
    }

    public function save(): void
    {
        $data = $this->validate()['form'];
        $data['recorded_by'] = auth()->id();

        if ($this->isTeacherRole()) {
            if (! in_array((string) $data['section'], $this->teacherAllowedSectionKeys(), true)) {
                $this->addError('form.section', 'You can only input marks for your allowed sections.');
                return;
            }
            if (! in_array((string) $data['subject'], $this->teacherAllowedSubjectKeys(), true)) {
                $this->addError('form.subject', 'You can only input marks for your assigned subjects.');
                return;
            }
        }

        if ($this->editingId) {
            $mark = WeeklyExamMark::find($this->editingId);
            if (! $mark || ! $this->canModifyMark($mark)) {
                abort(403, 'Unauthorized action.');
            }
        }

        WeeklyExamMark::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->resetForm();
    }

    public function edit(int $markId): void
    {
        $mark = WeeklyExamMark::findOrFail($markId);
        if (! $this->canModifyMark($mark)) {
            abort(403, 'Unauthorized action.');
        }
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

    public function promptDelete(int $markId): void
    {
        $mark = WeeklyExamMark::with('student')->find($markId);
        if (! $mark) {
            return;
        }
        if (! $this->canModifyMark($mark)) {
            abort(403, 'Unauthorized action.');
        }

        $this->confirmingDeleteId = $mark->id;
        $this->confirmingDeleteName = $mark->student->name ?? 'Mark';
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        $this->confirmingDeleteName = '';
    }

    public function deleteConfirmed(): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        $mark = WeeklyExamMark::find($this->confirmingDeleteId);
        if (! $mark || ! $this->canModifyMark($mark)) {
            abort(403, 'Unauthorized action.');
        }

        $mark->delete();
        $this->cancelDelete();
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
        if ($this->isTeacherRole() && ! in_array((string) $this->sectionFilter, $this->teacherAllowedSectionKeys(), true)) {
            $this->sectionFilter = (string) (array_key_first($this->availableSectionOptions()) ?? '');
        }
        $this->subjectFilter = 'all';
    }

    public function updatedFormSection(): void
    {
        if ($this->isTeacherRole() && ! in_array((string) $this->form['section'], $this->teacherAllowedSectionKeys(), true)) {
            $this->form['section'] = (string) (array_key_first($this->availableSectionOptions()) ?? '');
        }
        $this->form['student_id'] = '';
        $this->ensureFormSubject();
    }

    public function updatedFormClassLevel(): void
    {
        $this->form['student_id'] = '';
    }

    protected function defaultSubjectForSection(?string $section): string
    {
        return array_key_first($this->availableSubjectOptionsForSection($section)) ?? '';
    }

    protected function ensureFormSubject(): void
    {
        $section = $this->form['section'] ?: $this->sectionFilter;
        $available = $this->availableSubjectOptionsForSection($section);
        if (! array_key_exists($this->form['subject'] ?? '', $available)) {
            $this->form['subject'] = array_key_first($available) ?? '';
        }
    }

    protected function canModifyMark(WeeklyExamMark $mark): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['admin', 'director', 'assistant'], true)) {
            return true;
        }

        return (int) $mark->recorded_by === (int) $user->id;
    }

    protected function isTeacherRole(): bool
    {
        return in_array(auth()->user()?->role, ['instructor', 'lead_instructor'], true);
    }

    protected function availableSectionOptions(): array
    {
        $sections = AcademyOptions::sections();
        if (! $this->isTeacherRole()) {
            return $sections;
        }

        $allowed = $this->teacherAllowedSectionKeys();
        if (empty($allowed)) {
            return [];
        }

        return collect($sections)
            ->filter(fn ($label, $key) => in_array((string) $key, $allowed, true))
            ->toArray();
    }

    protected function availableSubjectOptionsForSection(?string $section): array
    {
        $options = AcademyOptions::subjectsForSection($section);
        if (! $this->isTeacherRole()) {
            return $options;
        }

        $allowed = $this->teacherAllowedSubjectKeys();
        if (empty($allowed)) {
            return [];
        }

        return collect($options)
            ->filter(fn ($label, $key) => in_array((string) $key, $allowed, true))
            ->toArray();
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

    protected function teacherAssignedValues()
    {
        $teacher = $this->resolveTeacher();
        if (! $teacher) {
            return collect();
        }

        $raw = collect($teacher->subjects ?? [])
            ->filter()
            ->map(fn ($subject) => (string) $subject)
            ->values();

        if ($raw->isEmpty() && ! empty($teacher->subject)) {
            $raw->push((string) $teacher->subject);
        }

        return $raw;
    }

    protected function teacherAllowedSubjectKeys(): array
    {
        if (! $this->isTeacherRole()) {
            return [];
        }

        $raw = $this->teacherAssignedValues();
        if ($raw->isEmpty()) {
            return [];
        }

        $allSubjects = AcademyOptions::subjects();
        $labelToKey = [];
        foreach ($allSubjects as $key => $label) {
            $labelToKey[strtolower(trim((string) $label))] = (string) $key;
        }

        $sectionKeys = array_keys(AcademyOptions::sections());
        $resolved = [];

        foreach ($raw as $item) {
            $value = strtolower(trim((string) $item));
            if ($value === '') {
                continue;
            }

            if (array_key_exists($value, $allSubjects)) {
                $resolved[] = $value;
                continue;
            }

            if (isset($labelToKey[$value])) {
                $resolved[] = $labelToKey[$value];
                continue;
            }

            if (in_array($value, $sectionKeys, true)) {
                $resolved = array_merge($resolved, array_keys(AcademyOptions::subjectsForSection($value)));
            }
        }

        return collect($resolved)->filter()->unique()->values()->toArray();
    }

    protected function teacherAllowedSectionKeys(): array
    {
        if (! $this->isTeacherRole()) {
            return array_keys(AcademyOptions::sections());
        }

        $allowedSections = [];
        $rawAssigned = $this->teacherAssignedValues();
        $subjects = $this->teacherAllowedSubjectKeys();

        $globalPrefixes = ['bangla_', 'english_'];
        $globalExact = ['ict'];

        foreach ($subjects as $subject) {
            $normalized = strtolower(trim((string) $subject));
            if (in_array($normalized, $globalExact, true)) {
                return array_keys(AcademyOptions::sections());
            }

            foreach ($globalPrefixes as $prefix) {
                if (str_starts_with($normalized, $prefix)) {
                    return array_keys(AcademyOptions::sections());
                }
            }
        }

        foreach ($rawAssigned as $item) {
            $value = strtolower(trim((string) $item));
            if (array_key_exists($value, AcademyOptions::sections())) {
                $allowedSections[] = $value;
            }
        }

        $sectionSubjects = config('academy.subjects.by_section', []);
        foreach ($sectionSubjects as $sectionKey => $subjectMap) {
            $sectionSubjectKeys = array_map('strtolower', array_keys((array) $subjectMap));
            foreach ($subjects as $subject) {
                if (in_array(strtolower((string) $subject), $sectionSubjectKeys, true)) {
                    $allowedSections[] = (string) $sectionKey;
                    break;
                }
            }
        }

        $allowedSections = array_values(array_unique($allowedSections));
        if (empty($allowedSections)) {
            return array_keys(AcademyOptions::sections());
        }

        return $allowedSections;
    }

    protected function normalizeTeacherFilters(): void
    {
        $sectionOptions = $this->availableSectionOptions();
        if ($this->isTeacherRole() && ! empty($sectionOptions)) {
            if (! array_key_exists($this->sectionFilter, $sectionOptions)) {
                $this->sectionFilter = (string) array_key_first($sectionOptions);
            }
            if (! array_key_exists($this->form['section'], $sectionOptions)) {
                $this->form['section'] = (string) array_key_first($sectionOptions);
            }
        }
    }
}
