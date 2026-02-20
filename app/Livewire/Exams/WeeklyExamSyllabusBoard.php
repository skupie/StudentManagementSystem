<?php

namespace App\Livewire\Exams;

use App\Models\Teacher;
use App\Models\WeeklyExamAssignment;
use App\Models\WeeklyExamSyllabus;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class WeeklyExamSyllabusBoard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $classFilter = 'all';
    public string $sectionFilter = 'all';

    public array $form = [
        'week_start_date' => '',
        'title' => '',
        'class_level' => 'hsc_1',
        'section' => 'science',
        'subject' => '',
        'syllabus_details' => '',
    ];

    public function mount(): void
    {
        $this->form['week_start_date'] = now('Asia/Dhaka')->startOfWeek()->toDateString();
        $options = $this->availableExamDateOptions();
        if (! empty($options)) {
            $this->form['week_start_date'] = (string) array_key_first($options);
        }
        $this->ensureSection();
        $this->ensureSubject();
    }

    protected function rules(): array
    {
        $subjectKeys = array_keys($this->availableSubjectOptions());
        $examDateKeys = array_keys($this->availableExamDateOptions());
        $isTeacherRole = $this->isTeacherRole();
        $sectionKeys = array_keys($this->availableSectionOptions());

        return [
            'form.week_start_date' => $isTeacherRole
                ? ['required', Rule::in($examDateKeys)]
                : ['required', 'date'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.class_level' => ['required', Rule::in(array_keys(AcademyOptions::classes()))],
            'form.section' => ['required', Rule::in($sectionKeys)],
            'form.subject' => ['required', Rule::in($subjectKeys)],
            'form.syllabus_details' => ['required', 'string'],
        ];
    }

    public function render()
    {
        $isTeacherRole = $this->isTeacherRole();
        $teacher = $isTeacherRole ? $this->resolveTeacher() : null;
        $sectionOptions = $this->availableSectionOptions();

        if (! array_key_exists((string) ($this->form['section'] ?? ''), $sectionOptions)) {
            $this->form['section'] = (string) (array_key_first($sectionOptions) ?? '');
        }

        $syllabi = WeeklyExamSyllabus::query()
            ->when($isTeacherRole, fn ($q) => $q->where('created_by', auth()->id()))
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('syllabus_details', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->classFilter !== 'all', fn ($q) => $q->where('class_level', $this->classFilter))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->orderByDesc('week_start_date')
            ->orderBy('title')
            ->paginate(10);

        return view('livewire.exams.weekly-exam-syllabus-board', [
            'syllabi' => $syllabi,
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => $sectionOptions,
            'subjectOptions' => $this->availableSubjectOptions(),
            'examDateOptions' => $this->availableExamDateOptions(),
            'filterClassOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'filterSectionOptions' => ['all' => 'All Sections'] + $sectionOptions,
            'isTeacherRole' => $isTeacherRole,
            'teacherLinked' => (bool) $teacher,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate()['form'];
        $user = auth()->user();

        WeeklyExamSyllabus::create([
            'week_start_date' => $data['week_start_date'],
            'title' => $data['title'],
            'class_level' => $data['class_level'],
            'section' => $data['section'],
            'subject' => $data['subject'],
            'syllabus_details' => $data['syllabus_details'],
            'created_by' => auth()->id(),
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: 'Weekly exam syllabus saved.');
    }

    public function delete(int $id): void
    {
        $query = WeeklyExamSyllabus::query()->whereKey($id);
        $user = auth()->user();
        if (in_array($user?->role, ['teacher', 'lead_instructor'], true)) {
            $query->where('created_by', $user?->id);
        }

        $query->delete();
        $this->dispatch('notify', message: 'Syllabus deleted.');
    }

    public function updatedFormSection(): void
    {
        $this->ensureSubject();
        $this->ensureExamDate();
    }

    public function updatedFormClassLevel(): void
    {
        $this->ensureExamDate();
    }

    public function updatedFormSubject(): void
    {
        $this->ensureExamDate();
    }

    protected function resetForm(): void
    {
        $this->form['title'] = '';
        $this->form['syllabus_details'] = '';
        $this->form['week_start_date'] = now('Asia/Dhaka')->startOfWeek()->toDateString();
        $this->ensureSection();
        $this->ensureExamDate();
        $this->ensureSubject();
        $this->resetValidation();
    }

    protected function ensureSubject(): void
    {
        $this->ensureSection();
        $available = $this->availableSubjectOptions();
        if (! array_key_exists($this->form['subject'] ?? '', $available)) {
            $this->form['subject'] = array_key_first($available) ?? '';
        }
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

    protected function availableSubjectOptions(): array
    {
        $section = (string) ($this->form['section'] ?? '');
        $sectionSubjects = AcademyOptions::subjectsForSection($section);
        if (! $this->isTeacherRole()) {
            return $sectionSubjects;
        }

        $allowed = $this->teacherAllowedSubjectKeys();
        if (empty($allowed)) {
            return [];
        }

        return collect($sectionSubjects)
            ->filter(fn ($label, $key) => in_array((string) $key, $allowed, true))
            ->toArray();
    }

    protected function ensureExamDate(): void
    {
        $options = $this->availableExamDateOptions();
        if (! array_key_exists((string) ($this->form['week_start_date'] ?? ''), $options)) {
            $this->form['week_start_date'] = (string) (array_key_first($options) ?? $this->form['week_start_date']);
        }
    }

    protected function availableExamDateOptions(): array
    {
        if (! $this->isTeacherRole()) {
            return [];
        }

        $teacher = $this->resolveTeacher();
        if (! $teacher) {
            return [];
        }

        return WeeklyExamAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('exam_date')
            ->get(['exam_date'])
            ->pluck('exam_date')
            ->mapWithKeys(function ($date) {
                $raw = \Carbon\Carbon::parse($date)->toDateString();
                return [$raw => \Carbon\Carbon::parse($date)->format('d M Y')];
            })
            ->toArray();
    }

    protected function isTeacherRole(): bool
    {
        return in_array(auth()->user()?->role, ['teacher', 'lead_instructor'], true);
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

    protected function ensureSection(): void
    {
        $sectionOptions = $this->availableSectionOptions();
        if (! array_key_exists((string) ($this->form['section'] ?? ''), $sectionOptions)) {
            $this->form['section'] = (string) (array_key_first($sectionOptions) ?? '');
        }
    }

    protected function teacherAssignedValues()
    {
        $teacher = $this->resolveTeacher();
        if (! $teacher) {
            return collect();
        }

        $raw = collect($teacher->subjects ?? [])
            ->filter()
            ->map(fn ($subject) => strtolower(trim((string) $subject)))
            ->filter()
            ->values();

        if ($raw->isEmpty() && ! empty($teacher->subject)) {
            $raw->push(strtolower(trim((string) $teacher->subject)));
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

        $sectionKeys = array_keys(AcademyOptions::sections());
        $resolved = [];

        foreach ($raw as $item) {
            $normalized = AcademyOptions::normalizeSubjectKey((string) $item);
            if ($normalized !== null) {
                $resolved[] = $normalized;
                continue;
            }

            if (in_array((string) $item, $sectionKeys, true)) {
                $resolved = array_merge($resolved, array_keys(AcademyOptions::subjectsForSection((string) $item)));
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

        foreach ($rawAssigned as $item) {
            if (array_key_exists((string) $item, AcademyOptions::sections())) {
                $allowedSections[] = (string) $item;
            }
        }

        foreach ($subjects as $subject) {
            $allowedSections = array_merge($allowedSections, AcademyOptions::sectionsForSubject((string) $subject));
        }

        $allowedSections = array_values(array_unique($allowedSections));
        if (empty($allowedSections)) {
            return array_keys(AcademyOptions::sections());
        }

        return $allowedSections;
    }
}
