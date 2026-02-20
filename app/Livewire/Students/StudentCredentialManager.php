<?php

namespace App\Livewire\Students;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class StudentCredentialManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $classFilter = 'all';
    public string $sectionFilter = 'all';
    public string $linkFilter = 'all';
    public string $bulkPin = '';
    public ?int $selectedStudentId = null;

    public array $form = [
        'password' => '',
        'password_confirmation' => '',
        'is_active' => true,
    ];

    public function mount(): void
    {
        $this->authorizePage();
    }

    public function render()
    {
        $students = $this->filteredStudentsQuery()->paginate(12);
        $accountsByStudent = $this->resolveAccountsByStudent($students->getCollection()->all());

        return view('livewire.students.student-credential-manager', [
            'students' => $students,
            'accountsByStudent' => $accountsByStudent,
            'classOptions' => ['all' => 'All Classes'] + \App\Support\AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + \App\Support\AcademyOptions::sections(),
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

    public function updatedLinkFilter(): void
    {
        $this->resetPage();
    }

    public function selectStudent(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
        $student = Student::find($studentId);
        if (! $student) {
            return;
        }

        $account = $this->resolveAccountForStudent($student);
        $this->form['is_active'] = (bool) ($account?->is_active ?? true);
        $this->form['password'] = '';
        $this->form['password_confirmation'] = '';
        $this->resetValidation();
    }

    public function saveCredentials(): void
    {
        $this->authorizePage();

        $this->validate([
            'selectedStudentId' => ['required', 'exists:students,id'],
            'form.password' => ['required', 'confirmed', Password::min(6)],
            'form.is_active' => ['required', 'boolean'],
        ]);

        $student = Student::findOrFail((int) $this->selectedStudentId);
        $mobile = trim((string) ($student->phone_number ?? ''));
        if ($mobile === '') {
            $this->addError('selectedStudentId', 'Student contact number is required before creating login credentials.');
            return;
        }

        $user = $this->resolveAccountForStudent($student);
        if (! $user) {
            $mobileConflict = User::query()
                ->where('contact_number', $mobile)
                ->where('role', '!=', 'student')
                ->exists();
            if ($mobileConflict) {
                $this->addError('selectedStudentId', 'This mobile number is already used by a non-student account.');
                return;
            }

            $user = User::where('contact_number', $mobile)->where('role', 'student')->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $student->name,
                'email' => $this->generateStudentEmail((int) $student->id),
                'role' => 'student',
                'contact_number' => $mobile,
                'is_active' => (bool) $this->form['is_active'],
                'password' => Hash::make((string) $this->form['password']),
            ]);
        } else {
            $user->update([
                'name' => $student->name,
                'role' => 'student',
                'contact_number' => $mobile,
                'is_active' => (bool) $this->form['is_active'],
                'password' => Hash::make((string) $this->form['password']),
            ]);
        }

        // Link student record to login account when the column exists and no conflict exists.
        if (Schema::hasColumn('students', 'user_id')) {
            $linked = $this->linkStudentToUserSafely($student, $user);
            if (! $linked) {
                $this->notifyMessage('Student credentials updated, but login link was skipped because this user is already linked to another student.');
            }
        }

        $this->form['password'] = '';
        $this->form['password_confirmation'] = '';
        $this->notifyMessage('Student credentials updated successfully.');
    }

    public function resetFilteredPasswordsToDefault(): void
    {
        $this->authorizePage();

        $this->validate([
            'bulkPin' => ['required', 'string', 'max:50'],
        ]);

        if (! hash_equals($this->pinCode(), trim($this->bulkPin))) {
            $this->addError('bulkPin', 'Invalid PIN. Please try again.');
            $this->notifyMessage('Invalid PIN.');
            return;
        }

        $students = $this->filteredStudentsQuery()->get();
        if ($students->isEmpty()) {
            $this->notifyMessage('No students found for current filters.');
            $this->bulkPin = '';
            return;
        }

        $hashed = Hash::make('basic123');
        $updated = 0;
        $created = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $mobile = trim((string) ($student->phone_number ?? ''));
            if ($mobile === '') {
                $skipped++;
                continue;
            }

            $user = $this->resolveAccountForStudent($student);
            if (! $user) {
                $mobileConflict = User::query()
                    ->where('contact_number', $mobile)
                    ->where('role', '!=', 'student')
                    ->exists();
                if ($mobileConflict) {
                    $skipped++;
                    continue;
                }

                $user = User::where('contact_number', $mobile)->where('role', 'student')->first();
            }

            if (! $user) {
                $user = User::create([
                    'name' => $student->name,
                    'email' => $this->generateStudentEmail((int) $student->id),
                    'role' => 'student',
                    'contact_number' => $mobile,
                    'is_active' => true,
                    'password' => $hashed,
                ]);
                $created++;
            } else {
                $user->update([
                    'name' => $student->name,
                    'role' => 'student',
                    'contact_number' => $mobile,
                    'is_active' => true,
                    'password' => $hashed,
                ]);
                $updated++;
            }

            if (Schema::hasColumn('students', 'user_id')) {
                $this->linkStudentToUserSafely($student, $user);
            }
        }

        $this->bulkPin = '';
        $this->resetErrorBag('bulkPin');
        $this->notifyMessage("Student credentials updated. Default password set to basic123. Updated: {$updated}, Created: {$created}, Skipped: {$skipped}.");
    }

    public function voidFilteredPasswords(): void
    {
        $this->authorizePage();

        $this->validate([
            'bulkPin' => ['required', 'string', 'max:50'],
        ]);

        if (! hash_equals($this->pinCode(), trim($this->bulkPin))) {
            $this->addError('bulkPin', 'Invalid PIN. Please try again.');
            $this->notifyMessage('Invalid PIN.');
            return;
        }

        $students = $this->filteredStudentsQuery()->get();
        if ($students->isEmpty()) {
            $this->notifyMessage('No students found for current filters.');
            $this->bulkPin = '';
            return;
        }

        $voided = 0;
        $skipped = 0;

        foreach ($students as $student) {
            $user = $this->resolveAccountForStudent($student);
            if (! $user) {
                $skipped++;
                continue;
            }

            $user->update([
                'password' => Hash::make(Str::random(32)),
                'is_active' => false,
            ]);

            if (Schema::hasColumn('students', 'user_id') && (int) ($student->user_id ?? 0) === (int) $user->id) {
                $student->user_id = null;
                $student->save();
            }
            $voided++;
        }

        $this->bulkPin = '';
        $this->resetErrorBag('bulkPin');
        $this->notifyMessage("Student credentials updated. Passwords voided. Voided: {$voided}, Skipped: {$skipped}.");
    }

    protected function authorizePage(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'director', 'teacher', 'instructor', 'lead_instructor'], true), 403);
    }

    protected function filteredStudentsQuery(): Builder
    {
        $query = Student::query()
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->classFilter !== 'all', fn ($q) => $q->where('class_level', $this->classFilter))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->orderBy('name');

        if ($this->linkFilter === 'linked') {
            $query->whereNotNull('user_id')
                ->whereHas('loginUser', function ($q) {
                    $q->where('role', 'student')->where('is_active', true);
                });
        } elseif ($this->linkFilter === 'unlinked') {
            $query->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhereDoesntHave('loginUser', function ($userQ) {
                        $userQ->where('role', 'student')->where('is_active', true);
                    });
            });
        }

        return $query;
    }

    protected function pinCode(): string
    {
        $cached = Cache::get('transfer_pin_override');
        if ($cached) {
            return (string) $cached;
        }

        return (string) config('app.transfer_pin', env('TRANSFER_PIN', '1234'));
    }

    protected function notifyMessage(string $message): void
    {
        $this->dispatch('notify', message: $message);
        $this->dispatch('user-notify', message: $message);
    }

    protected function linkStudentToUserSafely(Student $student, User $user): bool
    {
        if (! Schema::hasColumn('students', 'user_id')) {
            return true;
        }

        if ((int) ($student->user_id ?? 0) === (int) $user->id) {
            return true;
        }

        $taken = Student::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $student->id)
            ->exists();

        if ($taken) {
            return false;
        }

        $student->user_id = $user->id;
        $student->save();

        return true;
    }

    protected function resolveAccountsByStudent(array $students): array
    {
        $userIds = collect($students)->pluck('user_id')->filter()->values();

        $users = User::query()
            ->where('role', 'student')
            ->when($userIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $userIds->all()), fn ($q) => $q->whereRaw('1 = 0'))
            ->get();

        $byId = $users->keyBy('id');

        $map = [];
        foreach ($students as $student) {
            $account = null;
            if (! empty($student->user_id) && isset($byId[$student->user_id])) {
                $account = $byId[$student->user_id];
            }
            $map[(int) $student->id] = $account;
        }

        return $map;
    }

    protected function resolveAccountForStudent(Student $student): ?User
    {
        if (! empty($student->user_id)) {
            $user = User::find($student->user_id);
            if ($user) {
                return $user;
            }
        }

        if (! empty($student->phone_number)) {
            return User::where('contact_number', $student->phone_number)->where('role', 'student')->first();
        }

        return null;
    }

    protected function generateStudentEmail(int $studentId): string
    {
        $base = 'student' . $studentId . '@basicacademy.local';
        if (! User::where('email', $base)->exists()) {
            return $base;
        }

        $suffix = 1;
        while (User::where('email', 'student' . $studentId . '+' . $suffix . '@basicacademy.local')->exists()) {
            $suffix++;
        }

        return 'student' . $studentId . '+' . $suffix . '@basicacademy.local';
    }
}
