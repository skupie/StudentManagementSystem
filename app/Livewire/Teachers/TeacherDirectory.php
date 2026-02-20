<?php

namespace App\Livewire\Teachers;

use App\Models\Teacher;
use App\Models\User;
use App\Support\AcademyOptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TeacherDirectory extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $subjectSearch = '';
    public ?int $editingId = null;
    public $importFile;
    public array $form = [
        'name' => '',
        'subject' => '',
        'subjects' => [],
        'payment' => '',
        'contact_number' => '',
        'is_active' => true,
        'note' => '',
        'available_days' => [],
        'password' => '',
        'password_confirmation' => '',
    ];

    public function render()
    {
        $dayOptions = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $subjectOptions = $this->subjectOptions();
        $filteredSubjectOptions = $this->filteredSubjectOptions($subjectOptions);

        $teachers = Teacher::query()
            ->with('loginUser')
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
            'filteredSubjectOptions' => $filteredSubjectOptions,
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
            'form.contact_number' => ['nullable', 'string', 'max:50', 'required_with:form.password', 'unique:teachers,contact_number,' . $this->editingId],
            'form.is_active' => ['required', 'boolean'],
            'form.note' => ['nullable', 'string'],
            'form.available_days' => ['array'],
            'form.available_days.*' => ['in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday'],
            'form.password' => ['nullable', 'confirmed', Password::min(6)],
        ])['form'];
        $currentTeacher = $this->editingId ? Teacher::with('loginUser')->find($this->editingId) : null;

        if (! $this->validateLoginSyncInput($data, $currentTeacher)) {
            return;
        }

        $subjects = $this->canonicalizeSubjects((array) ($data['subjects'] ?? []));
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
            $teacher = Teacher::findOrFail($this->editingId);
        } else {
            $teacher = Teacher::create([
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

        if (! $this->syncTeacherLogin($teacher, $data)) {
            return;
        }

        $this->resetForm();
        $this->resetPage();
        $this->dispatch('notify', message: 'Teacher saved.');
    }

    protected function canManage(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'director', 'teacher', 'instructor', 'lead_instructor'], true);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->subjectSearch = '';
        $this->form = [
            'name' => '',
            'subject' => '',
            'subjects' => [],
            'payment' => '',
            'contact_number' => '',
            'is_active' => true,
            'note' => '',
            'available_days' => [],
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function exportCsv()
    {
        $this->authorizeManage();

        $filename = 'teachers-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel
            fputcsv($handle, ['name', 'subjects', 'payment', 'contact_number', 'is_active', 'note', 'available_days']);

            Teacher::orderBy('name')->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $teacher) {
                    fputcsv($handle, [
                        $teacher->name,
                        implode(',', (array) ($teacher->subjects ?? ($teacher->subject ? [$teacher->subject] : []))),
                        $teacher->payment ?? '',
                        $teacher->contact_number ?? '',
                        $teacher->is_active ? '1' : '0',
                        $teacher->note ?? '',
                        implode(',', (array) ($teacher->available_days ?? [])),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function importCsv(): void
    {
        $this->authorizeManage();

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        // Store a copy to ensure readability across environments (esp. Windows).
        $tempStored = $this->importFile->store('imports');
        $handle = $tempStored ? Storage::readStream($tempStored) : false;
        if (! $handle) {
            $this->addError('importFile', 'Unable to open uploaded file.');
            if ($tempStored) {
                Storage::delete($tempStored);
            }
            return;
        }

        $header = fgetcsv($handle);
        if ($header && isset($header[0])) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // strip BOM
        }
        $header = $header ? array_map('strtolower', $header) : [];
        $expected = ['name', 'subjects', 'payment', 'contact_number', 'is_active', 'note', 'available_days'];
        $hasHeader = count(array_intersect($header, $expected)) >= 2;

        $imported = 0;
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

            $name = trim($record['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $subjects = array_values(array_filter(array_map('trim', explode(',', $record['subjects'] ?? ''))));
            $payment = is_numeric($record['payment'] ?? null) ? (float) $record['payment'] : null;
            $contact = trim($record['contact_number'] ?? '');
            $isActiveRaw = strtolower(trim($record['is_active'] ?? '1'));
            $isActive = in_array($isActiveRaw, ['1', 'true', 'yes', 'active'], true);
            $note = trim($record['note'] ?? '');
            $availableDays = array_values(array_filter(array_map('trim', explode(',', $record['available_days'] ?? ''))));
            $primarySubject = $subjects[0] ?? null;

            Teacher::updateOrCreate(
                ['name' => $name],
                [
                    'subject' => $primarySubject,
                    'subjects' => $subjects,
                    'payment' => $payment,
                    'contact_number' => $contact ?: null,
                    'is_active' => $isActive,
                    'note' => $note ?: null,
                    'available_days' => $availableDays,
                    'created_by' => auth()->id(),
                ]
            );
            $imported++;
        }

        fclose($handle);
        if ($tempStored) {
            Storage::delete($tempStored);
        }

        $this->importFile = null;
        $this->resetPage();
        if ($imported === 0) {
            $this->addError('importFile', 'No rows were imported. Please check the file headers and data.');
        } else {
            $this->dispatch('notify', message: "Import complete. {$imported} teacher(s) processed.");
        }
    }

    protected function authorizeManage(): void
    {
        if (! $this->canManage()) {
            abort(403);
        }
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
        $loadedSubjects = $this->canonicalizeSubjects((array) ($teacher->subjects ?? []));
        if (empty($loadedSubjects) && ! empty($teacher->subject)) {
            $loadedSubjects = $this->canonicalizeSubjects([(string) $teacher->subject]);
        }

        $this->form = [
            'name' => $teacher->name,
            'subject' => $teacher->subject ?? '',
            'subjects' => $loadedSubjects,
            'payment' => $teacher->payment ?? '',
            'contact_number' => $teacher->contact_number ?? '',
            'is_active' => (bool) $teacher->is_active,
            'note' => $teacher->note ?? '',
            'available_days' => $teacher->available_days ?? [],
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->subjectSearch = '';
    }

    public function addSubject(string $subjectKey): void
    {
        $subjectKey = trim($subjectKey);
        if ($subjectKey === '') {
            return;
        }

        $subjectOptions = $this->subjectOptions();
        if (! array_key_exists($subjectKey, $subjectOptions)) {
            return;
        }

        $subjects = $this->canonicalizeSubjects((array) ($this->form['subjects'] ?? []));
        if (! in_array($subjectKey, $subjects, true)) {
            $subjects[] = $subjectKey;
        }

        $this->form['subjects'] = $subjects;
        $this->form['subject'] = $subjects[0] ?? '';
        $this->subjectSearch = '';
    }

    public function removeSubject(string $subjectKey): void
    {
        $subjects = $this->canonicalizeSubjects((array) ($this->form['subjects'] ?? []));
        $subjects = array_values(array_filter($subjects, fn ($item) => (string) $item !== (string) $subjectKey));

        $this->form['subjects'] = $subjects;
        $this->form['subject'] = $subjects[0] ?? '';
    }

    protected function syncTeacherLogin(Teacher $teacher, array $data): bool
    {
        $mobile = trim((string) ($data['contact_number'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $linkedUser = $teacher->loginUser;
        $needsAccountSync = $linkedUser !== null || $password !== '';

        if (! $needsAccountSync) {
            return true;
        }

        if ($mobile === '') {
            $this->addError('form.contact_number', 'Contact number is required for teacher login.');
            return false;
        }

        if (! $linkedUser) {
            $linkedUser = User::create([
                'name' => $teacher->name,
                'email' => $this->generateTeacherEmail($teacher->id),
                'role' => 'teacher',
                'subject' => $teacher->subject,
                'payment' => $teacher->payment,
                'contact_number' => $mobile,
                'is_active' => (bool) $teacher->is_active,
                'password' => Hash::make($password),
            ]);

            $teacher->user_id = $linkedUser->id;
            $teacher->save();
            return true;
        }

        $updates = [
            'name' => $teacher->name,
            'subject' => $teacher->subject,
            'payment' => $teacher->payment,
            'contact_number' => $mobile,
            'is_active' => (bool) $teacher->is_active,
        ];
        if ($password !== '') {
            $updates['password'] = Hash::make($password);
        }

        $linkedUser->update($updates);

        return true;
    }

    protected function validateLoginSyncInput(array $data, ?Teacher $teacher): bool
    {
        $mobile = trim((string) ($data['contact_number'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $hasExistingLogin = (bool) ($teacher?->user_id);
        $needsAccountSync = $hasExistingLogin || $password !== '';

        if (! $needsAccountSync) {
            return true;
        }

        if ($mobile === '') {
            $this->addError('form.contact_number', 'Contact number is required for teacher login.');
            return false;
        }

        $existingUserId = $teacher?->loginUser?->id;
        $mobileConflict = User::query()
            ->where('contact_number', $mobile)
            ->when($existingUserId, fn ($q) => $q->where('id', '!=', $existingUserId))
            ->exists();
        if ($mobileConflict) {
            $this->addError('form.contact_number', 'This mobile number is already used by another user account.');
            return false;
        }

        return true;
    }

    protected function generateTeacherEmail(int $teacherId): string
    {
        $base = 'teacher' . $teacherId . '@basicacademy.local';
        if (! User::where('email', $base)->exists()) {
            return $base;
        }

        $suffix = 1;
        while (User::where('email', 'teacher' . $teacherId . '+' . $suffix . '@basicacademy.local')->exists()) {
            $suffix++;
        }

        return 'teacher' . $teacherId . '+' . $suffix . '@basicacademy.local';
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

    protected function filteredSubjectOptions(array $subjectOptions): array
    {
        $selected = $this->canonicalizeSubjects((array) ($this->form['subjects'] ?? []));
        $search = strtolower(trim($this->subjectSearch));
        if ($search === '') {
            return [];
        }

        return collect($subjectOptions)
            ->reject(fn ($label, $key) => in_array((string) $key, $selected, true))
            ->filter(function ($label, $key) use ($search) {
                return str_contains(strtolower((string) $label), $search)
                    || str_contains(strtolower((string) $key), $search);
            })
            ->take(30)
            ->toArray();
    }

    protected function canonicalizeSubjects(array $subjects): array
    {
        $subjectOptions = $this->subjectOptions();
        $canonical = [];

        foreach ($subjects as $subject) {
            $value = trim((string) $subject);
            if ($value === '') {
                continue;
            }

            if (array_key_exists($value, $subjectOptions)) {
                $canonical[] = $value;
                continue;
            }

            $normalized = AcademyOptions::normalizeSubjectKey($value);
            if ($normalized !== null && array_key_exists($normalized, $subjectOptions)) {
                $canonical[] = $normalized;
                continue;
            }
        }

        return array_values(array_unique($canonical));
    }
}
