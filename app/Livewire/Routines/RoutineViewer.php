<?php

namespace App\Livewire\Routines;

use App\Models\Routine;
use App\Support\AcademyOptions;
use Livewire\Component;

class RoutineViewer extends Component
{
    public string $viewDate = '';

    public function mount(): void
    {
        $this->viewDate = now()->toDateString();
    }

    public function render()
    {
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

        return view('livewire.routines.viewer', [
            'tables' => $tables,
            'viewDate' => $this->viewDate,
        ]);
    }
}
