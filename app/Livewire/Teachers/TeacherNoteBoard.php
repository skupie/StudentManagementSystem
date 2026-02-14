<?php

namespace App\Livewire\Teachers;

use App\Models\TeacherNote;
use App\Support\AcademyOptions;
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
        'class_levels' => ['hsc_1'],
        'sections' => ['science'],
    ];

    public $uploadFile;
    public ?int $editingId = null;
    public ?int $confirmingDeleteId = null;
    public string $confirmingDeleteTitle = '';

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
            'form.class_levels' => ['required', 'array', 'min:1'],
            'form.class_levels.*' => [Rule::in(array_keys(AcademyOptions::classes()))],
            'form.sections' => ['required', 'array', 'min:1'],
            'form.sections.*' => [Rule::in(array_keys(AcademyOptions::sections()))],
            'uploadFile' => [$this->editingId ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function render()
    {
        $user = auth()->user();

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
            'filterClassOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'filterSectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
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

    public function save(): void
    {
        $isEditing = (bool) $this->editingId;
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
            'class_levels' => $note->classTargets(),
            'sections' => $note->sectionTargets(),
        ];
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
            'class_levels' => ! empty($this->form['class_levels']) ? $this->form['class_levels'] : ['hsc_1'],
            'sections' => ! empty($this->form['sections']) ? $this->form['sections'] : ['science'],
        ];

        $this->uploadFile = null;
        $this->resetValidation();
    }

    protected function canManageNote(TeacherNote $note): bool
    {
        $user = auth()->user();
        return in_array($user?->role, ['admin', 'director'], true) || (int) $note->uploaded_by === (int) $user?->id;
    }
}
