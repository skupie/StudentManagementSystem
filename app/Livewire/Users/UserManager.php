<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $pinReset = '';
    public string $artisanPinReset = '';

    public array $form = [
        'name' => '',
        'email' => '',
        'role' => 'teacher',
        'password' => '',
        'password_confirmation' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'form.role' => ['required', 'in:admin,director,teacher,instructor,assistant'],
            'form.password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.users.user-manager', [
            'users' => $users,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate()['form'];

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $this->resetForm();

        $this->dispatch('user-notify', message: 'User created successfully.');
    }

    public function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'role' => 'teacher',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->resetErrorBag();
    }

    public function toggleStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        if ($user->id === auth()->id()) {
            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $this->dispatch('notify', message: 'User status updated.');
    }

    public function resetTransferPin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $this->validate([
            'pinReset' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        \Illuminate\Support\Facades\Cache::forever('transfer_pin_override', $this->pinReset);
        $this->pinReset = '';

        $this->dispatch('user-notify', message: 'Transfer PIN reset successfully.');
    }

    public function resetArtisanPin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $this->validate([
            'artisanPinReset' => ['required', 'string', 'min:4', 'max:20'],
        ]);

        \Illuminate\Support\Facades\Cache::forever('artisan_pin_override', $this->artisanPinReset);
        $this->artisanPinReset = '';

        $this->dispatch('user-notify', message: 'Artisan PIN reset successfully.');
    }
}
