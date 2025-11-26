<?php

namespace App\Livewire\Teachers;

use App\Models\Teacher;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherDirectory extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $editingId = null;
    public array $form = [
        'name' => '',
        'subject' => '',
        'payment' => '',
        'contact_number' => '',
        'is_active' => true,
        'note' => '',
    ];

    public function render()
    {
        $teachers = Teacher::query()
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('subject', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(10);

        $hidePayment = auth()->user()?->role === 'assistant';

        return view('livewire.teachers.teacher-directory', [
            'teachers' => $teachers,
            'hidePayment' => $hidePayment,
            'canCreate' => $this->canManage(),
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.subject' => ['nullable', 'string', 'max:255'],
            'form.payment' => ['nullable', 'numeric', 'min:0'],
            'form.contact_number' => ['nullable', 'string', 'max:50'],
            'form.is_active' => ['required', 'boolean'],
            'form.note' => ['nullable', 'string'],
        ])['form'];

        if ($this->editingId) {
            Teacher::whereKey($this->editingId)->update([
                'name' => $data['name'],
                'subject' => $data['subject'],
                'payment' => $data['payment'] ?: null,
                'contact_number' => $data['contact_number'],
                'is_active' => (bool) $data['is_active'],
                'note' => $data['note'],
            ]);
        } else {
            Teacher::create([
                'name' => $data['name'],
                'subject' => $data['subject'],
                'payment' => $data['payment'] ?: null,
                'contact_number' => $data['contact_number'],
                'is_active' => (bool) $data['is_active'],
                'note' => $data['note'],
                'created_by' => auth()->id(),
            ]);
        }

        $this->resetForm();
        $this->resetPage();
        $this->dispatch('notify', message: 'Teacher saved.');
    }

    protected function canManage(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'instructor'], true);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'subject' => '',
            'payment' => '',
            'contact_number' => '',
            'is_active' => true,
            'note' => '',
        ];
    }

    public function edit(int $teacherId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $teacher = Teacher::find($teacherId);
        if (! $teacher) {
            return;
        }

        $this->editingId = $teacher->id;
        $this->form = [
            'name' => $teacher->name,
            'subject' => $teacher->subject ?? '',
            'payment' => $teacher->payment ?? '',
            'contact_number' => $teacher->contact_number ?? '',
            'is_active' => (bool) $teacher->is_active,
            'note' => $teacher->note ?? '',
        ];
    }
}
