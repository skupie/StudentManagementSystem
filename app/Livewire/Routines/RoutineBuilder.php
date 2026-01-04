<?php

namespace App\Livewire\Routines;

use App\Models\Routine;
use App\Models\Teacher;
use App\Support\AcademyOptions;
use Livewire\Component;
use Livewire\WithFileUploads;

class RoutineBuilder extends Component
{
    use WithFileUploads;

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
    public $importFile;
    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        return [
            'form.class_level' => ['required', 'in:hsc_1,hsc_2'],
            'form.section' => ['required', 'in:science,humanities,business_studies'],
            'form.routine_date' => ['required', 'date'],
            'form.time_slot' => ['required', 'string', 'max:50'],
            'form.subject' => ['required', 'string', 'max:255'],
            'form.teacher_id' => ['nullable', 'exists:teachers,id'],
            'importFile' => ['nullable', 'file', 'mimes:csv,txt'],
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

    public function promptDelete(int $routineId): void
    {
        $this->confirmingDeleteId = $routineId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteConfirmed(): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        Routine::whereKey($this->confirmingDeleteId)->delete();
        $this->confirmingDeleteId = null;
        $this->dispatch('notify', message: 'Routine entry deleted.');
    }

    public function exportCsv()
    {
        $routines = Routine::query()
            ->orderBy('routine_date')
            ->orderBy('class_level')
            ->orderBy('section')
            ->orderBy('time_slot')
            ->get();

        $filename = 'routines-all-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($routines) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['class_level', 'section', 'routine_date', 'time_slot', 'subject', 'teacher_id']);
            foreach ($routines as $row) {
                fputcsv($handle, [
                    $row->class_level,
                    $row->section,
                    $row->routine_date,
                    $row->time_slot,
                    $row->subject,
                    $row->teacher_id,
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function importCsv(): void
    {
        $this->validateOnly('importFile');

        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->store('imports');
        $handle = $path ? \Storage::readStream($path) : false;
        if (! $handle) {
            $this->addError('importFile', 'Unable to read uploaded file.');
            return;
        }

        // Skip header
        $header = fgetcsv($handle);
        if ($header && isset($header[0])) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }
        $header = $header ? array_map(fn ($h) => strtolower(trim($h)), $header) : [];
        $expected = ['class_level', 'section', 'routine_date', 'time_slot', 'subject', 'teacher_id'];
        $hasHeader = count(array_intersect($header, $expected)) >= 3;

        while (($row = fgetcsv($handle)) !== false) {
            $record = [];
            if ($hasHeader) {
                foreach ($header as $index => $key) {
                    if (isset($row[$index])) {
                        $record[$key] = $row[$index];
                    }
                }
            } else {
                $record = array_combine($expected, array_pad($row, count($expected), null));
            }

            $class = trim($record['class_level'] ?? '');
            $section = trim($record['section'] ?? '');
            $date = trim($record['routine_date'] ?? '');
            $time = trim($record['time_slot'] ?? '');
            $subject = trim($record['subject'] ?? '');
            $teacherId = trim($record['teacher_id'] ?? '');

            if ($class === '' || $section === '' || $date === '' || $time === '' || $subject === '') {
                continue;
            }

            Routine::create([
                'class_level' => $class,
                'section' => $section,
                'routine_date' => $date,
                'time_slot' => $time,
                'subject' => $subject,
                'teacher_id' => $teacherId !== '' ? $teacherId : null,
                'created_by' => auth()->id(),
            ]);
        }

        fclose($handle);
        if ($path) {
            \Storage::delete($path);
        }

        $this->importFile = null;
        $this->dispatch('notify', message: 'Routine CSV import complete.');
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
