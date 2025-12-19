<?php

namespace App\Livewire\Routines;

use App\Models\Routine;
use App\Models\Teacher;
use App\Support\AcademyOptions;
use Livewire\Component;

class RoutineBuilder extends Component
{
    public array $form = [
        'class_level' => 'hsc_1',
        'section' => 'science',
        'routine_date' => '',
        'time_slot' => '',
        'subject' => '',
        'teacher_id' => '',
    ];

    public string $viewDate = '';
    public ?int $editingId = null;

    protected function rules(): array
    {
        return [
            'form.class_level' => ['required', 'in:hsc_1,hsc_2'],
            'form.section' => ['required', 'in:science,humanities,business_studies'],
            'form.routine_date' => ['required', 'date'],
            'form.time_slot' => ['required', 'string', 'max:50'],
            'form.subject' => ['required', 'string', 'max:255'],
            'form.teacher_id' => ['nullable', 'exists:teachers,id'],
        ];
    }

    public function mount(): void
    {
        $today = now('Asia/Dhaka')->toDateString();
        $this->form['routine_date'] = $today;
        $this->viewDate = $today;
    }

    public function edit(int $routineId): void
    {
        $routine = Routine::findOrFail($routineId);
        $this->editingId = $routine->id;
        $this->form = [
            'class_level' => $routine->class_level,
            'section' => $routine->section,
            'routine_date' => $routine->routine_date,
            'time_slot' => $routine->time_slot,
            'subject' => $routine->subject,
            'teacher_id' => $routine->teacher_id,
        ];
        $this->viewDate = $routine->routine_date;
    }

    public function cancelEdit(): void
    {
        $today = now('Asia/Dhaka')->toDateString();
        $this->editingId = null;
        $this->form['time_slot'] = '';
        $this->form['subject'] = '';
        $this->form['teacher_id'] = '';
        $this->viewDate = $this->viewDate ?: $today;
    }

    public function save(): void
    {
        $data = $this->validate()['form'];

        if ($this->editingId) {
            $routine = Routine::findOrFail($this->editingId);
            $routine->update([
                'class_level' => $data['class_level'],
                'section' => $data['section'],
                'routine_date' => $data['routine_date'],
                'time_slot' => $data['time_slot'],
                'subject' => $data['subject'],
                'teacher_id' => $data['teacher_id'] ?: null,
            ]);

        } else {
            $routine = Routine::create([
                'class_level' => $data['class_level'],
                'section' => $data['section'],
                'routine_date' => $data['routine_date'],
                'time_slot' => $data['time_slot'],
                'subject' => $data['subject'],
                'teacher_id' => $data['teacher_id'] ?: null,
                'created_by' => auth()->id(),
            ]);
        }

        // Reset only the entry-specific fields, keep selected date/class/section.
        $this->form['time_slot'] = '';
        $this->form['subject'] = '';
        $this->form['teacher_id'] = '';
        $this->editingId = null;

        $this->dispatch('notify', message: 'Routine entry saved.');
    }

    public function render()
    {
        $teachers = Teacher::query()
            ->orderBy('name')
            ->get();

        $classes = ['hsc_1', 'hsc_2'];
        $sections = ['science', 'humanities', 'business_studies'];

        $entries = Routine::query()
            ->with('teacher')
            ->when($this->viewDate, fn ($q) => $q->whereDate('routine_date', $this->viewDate))
            ->orderBy('class_level')
            ->orderBy('section')
            ->orderBy('time_slot')
            ->get()
            ->groupBy(fn ($r) => $r->class_level . '|' . $r->section);

        $tables = [];
        foreach ($classes as $class) {
            foreach ($sections as $section) {
                $key = $class . '|' . $section;
                $tables[$key] = [
                    'class_label' => AcademyOptions::classLabel($class),
                    'section_label' => AcademyOptions::sectionLabel($section),
                    'rows' => $entries->get($key, collect()),
                ];
            }
        }

        return view('livewire.routines.builder', [
            'teachers' => $teachers,
            'classOptions' => AcademyOptions::classes(),
            'sectionOptions' => AcademyOptions::sections(),
            'tables' => $tables,
            'viewDate' => $this->viewDate,
        ]);
    }
}
