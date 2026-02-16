<?php

namespace App\Livewire\Teachers;

use App\Models\Teacher;
use App\Models\TeacherNote;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TeacherNoteBoard extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $classFilter = 'all';
    public string $sectionFilter = 'all';

    public array $form = [
        'title' => '',
        'description' => '',
        'subject' => '',
        'class_levels' => ['hsc_1'],
        'sections' => ['science'],
    ];

    public $uploadFile;
    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;
    public string $confirmingDeleteTitle = '';

    protected function rules(): array
    {
        $isTeacherRole = $this->isTeacherRole();
        $allowedSubjectKeys = array_keys($this->availableSubjectOptions());
        $sectionRules = ['required', 'array', 'min:1'];
        if ($isTeacherRole && ! $this->canSelectMultipleSections()) {
            $sectionRules[] = 'max:1';
        }

        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
            'form.subject' => $isTeacherRole
                ? ['required', Rule::in($allowedSubjectKeys)]
                : ['nullable', Rule::in(array_keys(AcademyOptions::subjects()))],
            'form.class_levels' => ['required', 'array', 'min:1'],
            'form.class_levels.*' => [Rule::in(array_keys(AcademyOptions::classes()))],
            'form.sections' => $sectionRules,
            'form.sections.*' => [Rule::in(array_keys(AcademyOptions::sections()))],
            'uploadFile' => [$this->editingId ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function mount(): void
    {
        $this->ensureSubjectSelection();
        $this->enforceSectionSelectionRule();
    }

    public function render()
    {
        $user = auth()->user();
        $subjectOptions = $this->availableSubjectOptions();
        $isTeacherRole = $this->isTeacherRole();
        $teacherLinked = $isTeacherRole ? (bool) $this->resolveTeacher() : true;
        $filterSectionOptions = $isTeacherRole ? $this->filterSectionOptionsForTeacher() : (['all' => 'All Sections'] + AcademyOptions::sections());

        if (! array_key_exists($this->sectionFilter, $filterSectionOptions)) {
            $this->sectionFilter = (string) (array_key_first($filterSectionOptions) ?? 'all');
        }

        $notes = TeacherNote::query()
            ->with('uploader')
            ->when(in_array($user?->role, ['instructor', 'lead_instructor'], true), function ($query) use ($user) {
                $query->where('uploaded_by', $user?->id);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->classFilter !== 'all', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('class_level', $this->classFilter)
                        ->orWhereJsonContains('target_classes', $this->classFilter);
                });
            })
            ->when($this->sectionFilter !== 'all', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('section', $this->sectionFilter)
                        ->orWhereJsonContains('target_sections', $this->sectionFilter);
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.teachers.teacher-note-board', [
            'notes' => $notes,
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => AcademyOptions::sections(),
            'subjectOptions' => $subjectOptions,
            'filterClassOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'filterSectionOptions' => $filterSectionOptions,
            'isTeacherRole' => $isTeacherRole,
            'teacherLinked' => $teacherLinked,
            'allowMultipleSections' => $this->canSelectMultipleSections(),
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedClassFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFormSubject(): void
    {
        $this->ensureSubjectSelection();
        $this->enforceSectionSelectionRule();
    }

    public function updatedFormSections(): void
    {
        $this->enforceSectionSelectionRule();
    }

    public function save(): void
    {
        $isEditing = (bool) $this->editingId;
        $this->ensureSubjectSelection();
        $this->enforceSectionSelectionRule();
        $validated = $this->validate();
        $note = $this->editingId ? TeacherNote::find($this->editingId) : null;
        if ($this->editingId && (! $note || ! $this->canManageNote($note))) {
            abort(403, 'Unauthorized action.');
        }

        $path = $note?->file_path;
        $originalName = $note?->original_name;
        $mimeType = $note?->mime_type;
        $fileSize = $note?->file_size ?? 0;

        if ($this->uploadFile) {
            $path = $this->uploadFile->store('teacher-notes', 'public');
            $originalName = $this->uploadFile->getClientOriginalName();
            $mimeType = $this->uploadFile->getMimeType();
            $fileSize = $this->uploadFile->getSize() ?? 0;

            if ($note?->file_path && Storage::disk('public')->exists($note->file_path)) {
                Storage::disk('public')->delete($note->file_path);
            }
        }
        $classTargets = array_values(array_unique(array_filter($validated['form']['class_levels'] ?? [])));
        $sectionTargets = array_values(array_unique(array_filter($validated['form']['sections'] ?? [])));

        TeacherNote::updateOrCreate(['id' => $this->editingId], [
            'title' => $validated['form']['title'],
            'description' => $validated['form']['description'],
            'subject' => $validated['form']['subject'] ?: null,
            'class_level' => $classTargets[0] ?? 'hsc_1',
            'section' => $sectionTargets[0] ?? 'science',
            'target_classes' => $classTargets,
            'target_sections' => $sectionTargets,
            'file_path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => $note?->uploaded_by ?? auth()->id(),
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: $isEditing ? 'Note updated successfully.' : 'Note uploaded successfully.');
    }

    public function edit(int $noteId): void
    {
        $note = TeacherNote::find($noteId);
        if (! $note || ! $this->canManageNote($note)) {
            abort(403, 'Unauthorized action.');
        }

        $this->editingId = $note->id;
        $this->form = [
            'title' => $note->title,
            'description' => (string) ($note->description ?? ''),
            'subject' => (string) ($note->subject ?? ''),
            'class_levels' => $note->classTargets(),
            'sections' => $note->sectionTargets(),
        ];
        $this->ensureSubjectSelection();
        $this->enforceSectionSelectionRule();
        $this->uploadFile = null;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function promptDelete(int $noteId): void
    {
        $note = TeacherNote::find($noteId);
        if (! $note || ! $this->canManageNote($note)) {
            abort(403, 'Unauthorized action.');
        }

        $this->confirmingDeleteId = $note->id;
        $this->confirmingDeleteTitle = $note->title;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
        $this->confirmingDeleteTitle = '';
    }

    public function deleteConfirmed(): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        $note = TeacherNote::find($this->confirmingDeleteId);
        if (! $note) {
            $this->cancelDelete();
            return;
        }

        if (! $this->canManageNote($note)) {
            abort(403, 'Unauthorized action.');
        }

        if ($note->file_path && Storage::disk('public')->exists($note->file_path)) {
            Storage::disk('public')->delete($note->file_path);
        }

        $note->delete();
        $this->cancelDelete();
        $this->dispatch('notify', message: 'Note deleted.');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'title' => '',
            'description' => '',
            'subject' => '',
            'class_levels' => ! empty($this->form['class_levels']) ? $this->form['class_levels'] : ['hsc_1'],
            'sections' => ! empty($this->form['sections']) ? $this->form['sections'] : ['science'],
        ];

        $this->ensureSubjectSelection();
        $this->enforceSectionSelectionRule();
        $this->uploadFile = null;
        $this->resetValidation();
    }

    protected function canManageNote(TeacherNote $note): bool
    {
        $user = auth()->user();
        return in_array($user?->role, ['admin', 'director'], true) || (int) $note->uploaded_by === (int) $user?->id;
    }

    protected function isTeacherRole(): bool
    {
        $user = auth()->user();
        return in_array($user?->role, ['instructor', 'lead_instructor'], true);
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
        $allSubjects = AcademyOptions::subjects();
        if (! $this->isTeacherRole()) {
            return $allSubjects;
        }

        $teacher = $this->resolveTeacher();
        if (! $teacher) {
            return [];
        }

        $assigned = collect($teacher->subjects ?? [])
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->values();

        if ($assigned->isEmpty() && ! empty($teacher->subject)) {
            $assigned->push((string) $teacher->subject);
        }

        if ($assigned->isEmpty()) {
            return [];
        }

        return collect($allSubjects)
            ->filter(fn ($label, $key) => $assigned->contains((string) $key))
            ->toArray();
    }

    protected function filterSectionOptionsForTeacher(): array
    {
        $sections = AcademyOptions::sections();
        if ($this->teacherHasGlobalSubjects()) {
            return ['all' => 'All Sections'] + $sections;
        }

        $allowedKeys = $this->teacherAllowedSectionKeys();
        if (empty($allowedKeys)) {
            return ['all' => 'All Sections'] + $sections;
        }

        return collect($sections)
            ->filter(fn ($label, $key) => in_array((string) $key, $allowedKeys, true))
            ->toArray();
    }

    protected function teacherHasGlobalSubjects(): bool
    {
        $subjects = array_map('strtolower', array_keys($this->availableSubjectOptions()));
        foreach ($subjects as $subject) {
            if ($subject === 'ict' || str_starts_with($subject, 'bangla_') || str_starts_with($subject, 'english_')) {
                return true;
            }
        }

        return false;
    }

    protected function teacherAllowedSectionKeys(): array
    {
        if ($this->teacherHasGlobalSubjects()) {
            return array_keys(AcademyOptions::sections());
        }

        $subjectKeys = array_keys($this->availableSubjectOptions());
        $sectionSubjects = (array) config('academy.subjects.by_section', []);
        $allowedSections = [];

        foreach ($sectionSubjects as $sectionKey => $subjectMap) {
            $sectionSubjectKeys = array_map('strtolower', array_keys((array) $subjectMap));
            foreach ($subjectKeys as $subjectKey) {
                if (in_array(strtolower((string) $subjectKey), $sectionSubjectKeys, true)) {
                    $allowedSections[] = (string) $sectionKey;
                    break;
                }
            }
        }

        return array_values(array_unique($allowedSections));
    }

    protected function ensureSubjectSelection(): void
    {
        $subjectOptions = $this->availableSubjectOptions();
        if (! array_key_exists((string) ($this->form['subject'] ?? ''), $subjectOptions)) {
            $this->form['subject'] = (string) (array_key_first($subjectOptions) ?? '');
        }
    }

    protected function canSelectMultipleSections(): bool
    {
        if (! $this->isTeacherRole()) {
            return true;
        }

        $subject = (string) ($this->form['subject'] ?? '');
        if ($subject === 'ict') {
            return true;
        }

        return str_starts_with($subject, 'bangla_') || str_starts_with($subject, 'english_');
    }

    protected function enforceSectionSelectionRule(): void
    {
        $selectedSections = array_values(array_unique(array_filter((array) ($this->form['sections'] ?? []))));
        if (empty($selectedSections)) {
            $selectedSections = ['science'];
        }

        if (! $this->canSelectMultipleSections()) {
            $selectedSections = [reset($selectedSections)];
        }

        $this->form['sections'] = $selectedSections;
    }
}
