<?php

namespace App\Livewire\ModelTests;

use App\Models\ModelTest;
use App\Models\ModelTestResult;
use App\Models\ModelTestStudent;
use App\Models\Student;
use App\Support\AcademyOptions;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ModelTestManager extends Component
{
    public array $studentForm = [
        'name' => '',
        'contact_number' => '',
        'section' => '',
        'year' => '',
    ];

    public ?int $selectedStudentId = null;
    public ?int $existingStudentId = null;
    public string $existingClass = '';
    public string $existingSection = '';
    public string $marksSectionFilter = '';

    public array $testForm = [
        'name' => '',
        'subject' => '',
        'type' => 'full',
        'year' => '',
    ];

    public array $marksForm = [
        'student_id' => null,
        'model_test_id' => null,
        'year' => '',
        'subject' => '',
        'test_type' => 'full',
        'mcq_mark' => null,
        'cq_mark' => null,
        'practical_mark' => null,
        'mcq_max' => null,
        'cq_max' => null,
        'practical_max' => null,
        'optional_subject' => false,
    ];

    public function mount(): void
    {
        $currentYear = (int) now()->year;
        $this->studentForm['year'] = $currentYear;
        $this->testForm['year'] = $currentYear;
        $this->marksForm['year'] = $currentYear;
        $this->studentForm['section'] = array_key_first($this->sectionOptions()) ?? 'science';
        $this->existingSection = $this->studentForm['section'];
        $this->existingClass = array_key_first($this->classOptions()) ?? '';
        $defaultSubject = array_key_first($this->subjectOptions($this->studentForm['section'])) ?? '';
        $this->testForm['type'] = 'full';
        $this->testForm['name'] = '';
        $this->testForm['subject'] = $defaultSubject;
        $this->marksForm['subject'] = $defaultSubject;
        $this->marksForm['test_type'] = 'full';
        $this->initializeCustomMax(null, null, $defaultSubject);
    }

    public function render()
    {
        $students = ModelTestStudent::orderBy('name')->get();
        $marksSection = $this->normalizeSectionKey($this->marksSectionFilter);
        $marksStudents = ModelTestStudent::query()
            ->when($marksSection !== null && $marksSection !== '', function ($q) use ($marksSection) {
                $q->whereRaw('LOWER(section) = ?', [strtolower($marksSection)]);
            })
            ->orderBy('name')
            ->get();
        $existingStudents = Student::query()
            ->when($this->existingClass !== '', fn ($q) => $q->where('class_level', $this->existingClass))
            ->when($this->existingSection !== '', fn ($q) => $q->where('section', $this->existingSection))
            ->orderBy('name')
            ->get();
        $tests = ModelTest::orderByDesc('year')->orderBy('name')->get();
        $resolvedMarksSection = $this->currentMarksSection();
        $subjectOptions = $this->subjectOptions($resolvedMarksSection);
        $this->ensureSubjectDefault($subjectOptions);
        $this->initializeCustomMax($resolvedMarksSection, $this->marksForm['test_type'], $this->marksForm['subject']);

        $selectedTest = $tests->firstWhere('id', $this->marksForm['model_test_id']);
        $markType = $this->marksForm['test_type'] ?? ($selectedTest->type ?? 'full');
        $maxMarks = $this->maxMarks($resolvedMarksSection ?? 'science', $markType, $this->marksForm['subject'] ?? null);

        return view('livewire.model-tests.model-test-manager', [
            'students' => $students,
            'marksStudents' => $marksStudents,
            'existingStudents' => $existingStudents,
            'tests' => $tests,
            'sectionOptions' => $this->sectionOptions(),
            'classOptions' => $this->classOptions(),
            'subjectOptions' => $subjectOptions,
            'defaultYear' => now()->year,
            'marksSection' => $resolvedMarksSection,
            'markType' => $markType,
            'maxMarks' => $maxMarks,
        ]);
    }

    public function updatedExistingClass($value): void
    {
        $this->existingSection = '';
        $this->existingStudentId = null;
    }

    public function updatedExistingSection($value): void
    {
        $this->existingStudentId = null;
    }

    public function updatedMarksFormModelTestId($value): void
    {
        if (! $value) {
            return;
        }

        $test = ModelTest::find($value);
        if ($test) {
            $subjectOptions = $this->subjectOptions($this->currentMarksSection());
            $this->ensureSubjectDefault($subjectOptions);
            $this->marksForm['subject'] = $test->subject ?: ($this->marksForm['subject'] ?: array_key_first($subjectOptions));
            $this->marksForm['test_type'] = $test->type ?: 'full';
            $this->initializeCustomMax($this->currentMarksSection(), $this->marksForm['test_type'], $this->marksForm['subject']);
        }
    }

    public function updatedMarksFormStudentId($value): void
    {
        $section = $this->currentMarksSection();
        $this->resetSubjectForSection($section);
        $this->initializeCustomMax($section, $this->marksForm['test_type'], $this->marksForm['subject']);
    }

    public function updatedMarksSectionFilter($value): void
    {
        $normalized = $this->normalizeSectionKey($value);
        $this->marksSectionFilter = $normalized ?? '';
        $this->marksForm['student_id'] = null;
        $this->resetSubjectForSection($normalized ?: $this->currentMarksSection());
        $this->initializeCustomMax($normalized ?: $this->currentMarksSection(), $this->marksForm['test_type'], $this->marksForm['subject']);
    }

    public function updatedMarksFormSubject($value): void
    {
        $section = $this->currentMarksSection();
        $this->initializeCustomMax($section, $this->marksForm['test_type'], $value, true);
    }

    public function createStudent(): void
    {
        $data = $this->validate([
            'studentForm.name' => ['required', 'string', 'max:255'],
            'studentForm.contact_number' => ['nullable', 'string', 'max:50'],
            'studentForm.section' => ['required', Rule::in(array_keys($this->sectionOptions()))],
            'studentForm.year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ])['studentForm'];

        $student = ModelTestStudent::create($data);
        $this->selectedStudentId = $student->id;
        $this->marksForm['student_id'] = $student->id;

        $this->dispatch('notify', message: 'Model Test student saved.');

        $this->resetStudentForm();
        $this->existingStudentId = null;
    }

    public function loadStudent(?int $studentId): void
    {
        $student = $studentId ? ModelTestStudent::find($studentId) : null;
        if (! $student) {
            return;
        }

        $this->selectedStudentId = $student->id;
        $this->studentForm = [
            'name' => $student->name,
            'contact_number' => $student->contact_number ?? '',
            'section' => $student->section,
            'year' => $student->year,
        ];
        $this->marksForm['student_id'] = $student->id;
        $this->resetSubjectForSection($student->section);
    }

    public function useExistingStudent(): void
    {
        $studentId = $this->existingStudentId;
        if (! $studentId) {
            return;
        }

        $base = Student::find($studentId);
        if (! $base) {
            return;
        }

        $year = (int) ($base->academic_year ?? now()->year);
        $section = $base->section ?: ($this->existingSection ?: array_key_first($this->sectionOptions()) ?? 'science');
        $contact = $base->phone_number ?? '';

        // Prefill form only; saving happens via Save Student button
        $this->studentForm = [
            'name' => $base->name,
            'contact_number' => $contact,
            'section' => $section,
            'year' => $year,
        ];
        $this->selectedStudentId = null;
        $this->marksForm['student_id'] = null;
        $this->resetSubjectForSection($section);
        $this->initializeCustomMax($section, $this->marksForm['test_type'], $this->marksForm['subject'], true);

        $this->dispatch('notify', message: 'Student info loaded. Click "Save Student" to add to Model Test list.');
    }

    public function createModelTest(): void
    {
        $data = $this->validate([
            'testForm.name' => ['required', 'string', 'max:255'],
            'testForm.subject' => ['nullable', 'string', 'max:255'],
            'testForm.type' => ['required', Rule::in(['full', 'mcq', 'cq'])],
            'testForm.year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ])['testForm'];

        $data['subject'] = $data['subject'] ?? '';
        $data['subject'] = $data['subject'] !== '' ? $data['subject'] : (array_key_first($this->subjectOptions($this->currentMarksSection())) ?? 'general');

        $test = ModelTest::create($data);
        $this->marksForm['model_test_id'] = $test->id;
        $this->marksForm['subject'] = $test->subject;
        $this->marksForm['test_type'] = $test->type;

        $this->dispatch('notify', message: 'Model Test saved.');

        $this->resetTestForm();
    }

    public function saveMarks(): void
    {
        $validated = $this->validate($this->marksRules());
        $marks = $validated['marksForm'];

        $student = ModelTestStudent::find($marks['student_id']);
        $test = ModelTest::find($marks['model_test_id']);

        if (! $student || ! $test) {
            $this->addError('marksForm.student_id', 'Select valid student and test.');
            return;
        }

        if ($marks['subject'] && $test->subject !== $marks['subject']) {
            $test->update(['subject' => $marks['subject']]);
        }
        $section = $student->section;
        $type = $test->type ?: 'full';
        $max = $this->maxMarks($section, $type, $marks['subject'] ?? null);

        $mcq = $type !== 'cq' ? (float) ($marks['mcq_mark'] ?? 0) : null;
        $cq = $type !== 'mcq' ? (float) ($marks['cq_mark'] ?? 0) : null;
        $practical = $type === 'full' && $max['practical'] > 0
            ? (float) ($marks['practical_mark'] ?? 0)
            : null;

        $total = 0;
        if ($type === 'full') {
            $total = ($mcq ?? 0) + ($cq ?? 0) + ($practical ?? 0);
        } elseif ($type === 'mcq') {
            $total = $mcq ?? 0;
        } else {
            $total = $cq ?? 0;
        }

        $gradeData = ModelTestResult::gradeForScore($total);

        ModelTestResult::updateOrCreate(
            [
                'model_test_id' => $test->id,
                'model_test_student_id' => $student->id,
                'year' => (int) $marks['year'],
                'subject' => $marks['subject'],
            ],
            [
                'mcq_mark' => $mcq,
                'cq_mark' => $cq,
                'practical_mark' => $practical,
                'total_mark' => $total,
                'grade' => $gradeData['grade'],
                'grade_point' => $gradeData['point'],
                'optional_subject' => (bool) ($marks['optional_subject'] ?? false),
            ]
        );

        $this->marksForm['mcq_mark'] = null;
        $this->marksForm['cq_mark'] = null;
        $this->marksForm['practical_mark'] = null;

        $this->dispatch('notify', message: 'Marks saved.');
    }

    protected function marksRules(): array
    {
        $studentId = $this->marksForm['student_id'] ?? null;
        $testId = $this->marksForm['model_test_id'] ?? null;

        $student = $studentId ? ModelTestStudent::find($studentId) : null;
        $test = $testId ? ModelTest::find($testId) : null;

        $type = $test?->type ?? ($this->marksForm['test_type'] ?? 'full');
        $section = $student?->section ?? 'science';
        $max = $this->maxMarks($section, $type, $this->marksForm['subject'] ?? null);

        $rules = [
            'marksForm.student_id' => ['required', 'exists:model_test_students,id'],
            'marksForm.model_test_id' => ['required', 'exists:model_tests,id'],
            'marksForm.year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'marksForm.subject' => ['required', Rule::in(array_keys($this->subjectOptions($section)))],
            'marksForm.mcq_max' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'marksForm.cq_max' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'marksForm.practical_max' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'marksForm.optional_subject' => ['boolean'],
        ];

        if ($type === 'full') {
            $rules['marksForm.mcq_mark'] = ['required', 'numeric', 'min:0', 'max:' . $max['mcq']];
            $rules['marksForm.cq_mark'] = ['required', 'numeric', 'min:0', 'max:' . $max['cq']];
            if ($max['practical'] > 0) {
                $rules['marksForm.practical_mark'] = ['nullable', 'numeric', 'min:0', 'max:' . $max['practical']];
            }
        } elseif ($type === 'mcq') {
            $rules['marksForm.mcq_mark'] = ['required', 'numeric', 'min:0', 'max:' . $max['mcq']];
        } else {
            $rules['marksForm.cq_mark'] = ['required', 'numeric', 'min:0', 'max:' . $max['cq']];
        }

        return $rules;
    }

    protected function maxMarks(string $section, string $type, ?string $subject = null): array
    {
        $section = $this->normalizeSectionKey($section);
        $isScience = $section === 'science';

        $mcqMaxOverride = $this->marksForm['mcq_max'] ?? null;
        $cqMaxOverride = $this->marksForm['cq_max'] ?? null;
        $practicalMaxOverride = $this->marksForm['practical_max'] ?? null;

        if ($type === 'mcq') {
            return ['mcq' => $mcqMaxOverride ?? ($isScience ? 25 : 30), 'cq' => 0, 'practical' => 0];
        }

        if ($type === 'cq') {
            return ['mcq' => 0, 'cq' => $cqMaxOverride ?? ($isScience ? 50 : 70), 'practical' => 0];
        }

        // Full model test
        if ($isScience) {
            return [
                'mcq' => $mcqMaxOverride ?? 25,
                'cq' => $cqMaxOverride ?? 50,
                'practical' => $practicalMaxOverride ?? 25,
            ];
        }

        return [
            'mcq' => $mcqMaxOverride ?? 30,
            'cq' => $cqMaxOverride ?? 70,
            'practical' => $practicalMaxOverride ?? 0,
        ];
    }

    protected function sectionOptions(): array
    {
        return AcademyOptions::sections();
    }

    protected function classOptions(): array
    {
        return AcademyOptions::classes();
    }

    protected function subjectOptions(?string $section = null): array
    {
        $normalized = $this->normalizeSectionKey($section);
        return AcademyOptions::subjectsForSection($normalized);
    }

    protected function currentMarksSection(): ?string
    {
        if ($this->marksForm['student_id']) {
            $student = ModelTestStudent::find($this->marksForm['student_id']);
            if ($student) {
                return $this->normalizeSectionKey($student->section);
            }
        }

        if ($this->marksSectionFilter !== '') {
            return $this->normalizeSectionKey($this->marksSectionFilter);
        }

        $section = $this->studentForm['section'] ?: $this->existingSection;
        if (! $section) {
            $section = array_key_first($this->sectionOptions()) ?? 'science';
        }
        return $this->normalizeSectionKey($section);
    }

    protected function ensureSubjectDefault(array $subjectOptions): void
    {
        if (! $subjectOptions) {
            return;
        }

        if (! array_key_exists($this->marksForm['subject'] ?? '', $subjectOptions)) {
            $this->marksForm['subject'] = array_key_first($subjectOptions);
        }
    }

    protected function resetSubjectForSection(?string $section): void
    {
        $options = $this->subjectOptions($section);
        $this->ensureSubjectDefault($options);
    }

    protected function normalizeSectionKey(?string $section): ?string
    {
        if (! $section) {
            return null;
        }

        $section = strtolower(trim($section));
        $sections = $this->sectionOptions();
        foreach ($sections as $key => $label) {
            if ($section === strtolower($key) || $section === strtolower($label)) {
                return $key;
            }
        }

        return $section; // fallback to provided value
    }

    protected function initializeCustomMax(?string $section = null, ?string $type = null, ?string $subject = null, bool $forceReset = false): void
    {
        $section = $section ? $this->normalizeSectionKey($section) : $this->currentMarksSection();
        $type = $type ?: ($this->marksForm['test_type'] ?? 'full');
        $defaults = $this->maxMarks($section ?? 'science', $type ?: 'full', $subject ?? $this->marksForm['subject'] ?? null);

        if ($forceReset || $this->marksForm['mcq_max'] === null) {
            $this->marksForm['mcq_max'] = $defaults['mcq'];
        }
        if ($forceReset || $this->marksForm['cq_max'] === null) {
            $this->marksForm['cq_max'] = $defaults['cq'];
        }
        if ($forceReset || $this->marksForm['practical_max'] === null) {
            $this->marksForm['practical_max'] = $defaults['practical'];
        }
    }

    protected function resetStudentForm(): void
    {
        $currentYear = (int) now()->year;
        $defaultSection = array_key_first($this->sectionOptions()) ?? 'science';
        $defaultSubject = array_key_first($this->subjectOptions($defaultSection)) ?? '';

        $this->studentForm = [
            'name' => '',
            'contact_number' => '',
            'section' => $defaultSection,
            'year' => $currentYear,
        ];
        $this->selectedStudentId = null;
        $this->marksForm['student_id'] = null;
        $this->resetSubjectForSection($defaultSection);
        $this->initializeCustomMax($defaultSection, $this->marksForm['test_type'], $defaultSubject, true);
    }

    protected function resetTestForm(): void
    {
        $currentYear = (int) now()->year;
        $defaultSection = $this->currentMarksSection() ?? (array_key_first($this->sectionOptions()) ?? 'science');
        $defaultSubject = array_key_first($this->subjectOptions($defaultSection)) ?? '';

        $this->testForm = [
            'name' => '',
            'subject' => $defaultSubject,
            'type' => 'full',
            'year' => $currentYear,
        ];
        $this->marksForm['model_test_id'] = null;
    }
}
