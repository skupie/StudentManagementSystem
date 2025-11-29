<?php

namespace App\Livewire\Teachers;

use App\Models\Teacher;
use App\Support\AcademyOptions;
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
        'subjects' => [],
        'payment' => '',
        'contact_number' => '',
        'is_active' => true,
        'note' => '',
        'available_days' => [],
    ];

    public function render()
    {
        $dayOptions = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $subjectOptions = $this->subjectOptions();

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
            'dayOptions' => $dayOptions,
            'subjectOptions' => $subjectOptions,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.subject' => ['nullable', 'string', 'max:255'],
            'form.subjects' => ['array'],
            'form.subjects.*' => ['string'],
            'form.payment' => ['nullable', 'numeric', 'min:0'],
            'form.contact_number' => ['nullable', 'string', 'max:50'],
            'form.is_active' => ['required', 'boolean'],
            'form.note' => ['nullable', 'string'],
            'form.available_days' => ['array'],
            'form.available_days.*' => ['in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday'],
        ])['form'];

        $subjects = array_values(array_filter($data['subjects'] ?? []));
        $primarySubject = $subjects[0] ?? ($data['subject'] ?? null);

        if ($this->editingId) {
            Teacher::whereKey($this->editingId)->update([
                'name' => $data['name'],
                'subject' => $primarySubject,
                'subjects' => $subjects,
                'payment' => $data['payment'] ?: null,
                'contact_number' => $data['contact_number'],
                'is_active' => (bool) $data['is_active'],
                'note' => $data['note'],
                'available_days' => array_values($data['available_days'] ?? []),
            ]);
        } else {
            Teacher::create([
                'name' => $data['name'],
                'subject' => $primarySubject,
                'subjects' => $subjects,
                'payment' => $data['payment'] ?: null,
                'contact_number' => $data['contact_number'],
                'is_active' => (bool) $data['is_active'],
                'note' => $data['note'],
                'created_by' => auth()->id(),
                'available_days' => array_values($data['available_days'] ?? []),
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
            'subjects' => [],
            'payment' => '',
            'contact_number' => '',
            'is_active' => true,
            'note' => '',
            'available_days' => [],
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
            'subjects' => $teacher->subjects ?? [],
            'payment' => $teacher->payment ?? '',
            'contact_number' => $teacher->contact_number ?? '',
            'is_active' => (bool) $teacher->is_active,
            'note' => $teacher->note ?? '',
            'available_days' => $teacher->available_days ?? [],
        ];
    }

    protected function subjectOptions(): array
    {
        $subjects = [];
        $common = AcademyOptions::subjectsForSection('science');
        foreach (AcademyOptions::subjects() ?? [] as $key => $label) {
            $subjects[$key] = $label;
        }
        foreach (AcademyOptions::classes() as $classKey => $label) {
            $byClass = AcademyOptions::subjectsForSection($classKey) ?? [];
            foreach ($byClass as $key => $subjectLabel) {
                $subjects[$key] = $subjectLabel;
            }
        }
        foreach (AcademyOptions::sections() as $sectionKey => $label) {
            $bySection = AcademyOptions::subjectsForSection($sectionKey) ?? [];
            foreach ($bySection as $key => $subjectLabel) {
                $subjects[$key] = $subjectLabel;
            }
        }
        $subjects = array_merge($subjects, $common);
        return collect($subjects)->filter()->unique()->toArray();
    }
}
