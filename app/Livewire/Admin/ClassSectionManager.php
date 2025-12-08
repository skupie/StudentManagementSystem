<?php

namespace App\Livewire\Admin;

use App\Models\ClassOption;
use App\Models\SectionOption;
use Livewire\Component;

class ClassSectionManager extends Component
{
    public string $classKey = '';
    public string $classLabel = '';

    public string $sectionKey = '';
    public string $sectionLabel = '';

    protected function rules(): array
    {
        return [
            'classKey' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'classLabel' => ['nullable', 'string', 'max:100'],
            'sectionKey' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'sectionLabel' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function saveClass(): void
    {
        $this->validate([
            'classKey' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:class_options,key'],
            'classLabel' => ['required', 'string', 'max:100'],
        ]);

        ClassOption::create([
            'key' => $this->classKey,
            'label' => $this->classLabel,
            'is_active' => true,
        ]);

        cache()->forget('academy.classes');
        $this->reset(['classKey', 'classLabel']);
        $this->dispatch('notify', message: 'Class added.');
    }

    public function saveSection(): void
    {
        $this->validate([
            'sectionKey' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:section_options,key'],
            'sectionLabel' => ['required', 'string', 'max:100'],
        ]);

        SectionOption::create([
            'key' => $this->sectionKey,
            'label' => $this->sectionLabel,
            'is_active' => true,
        ]);

        cache()->forget('academy.sections');
        $this->reset(['sectionKey', 'sectionLabel']);
        $this->dispatch('notify', message: 'Section added.');
    }

    public function toggleClass(int $id): void
    {
        $item = ClassOption::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();
        cache()->forget('academy.classes');
    }

    public function toggleSection(int $id): void
    {
        $item = SectionOption::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();
        cache()->forget('academy.sections');
    }

    public function render()
    {
        return view('livewire.admin.class-section-manager', [
            'classes' => ClassOption::orderBy('label')->get(),
            'sections' => SectionOption::orderBy('label')->get(),
        ]);
    }
}
