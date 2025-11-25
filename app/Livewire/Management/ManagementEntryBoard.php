<?php

namespace App\Livewire\Management;

use App\Models\ManagementEntry;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ManagementEntryBoard extends Component
{
    use WithPagination;

    public string $signInAt = '';
    public string $signOutAt = '';
    public string $monthFilter = '';
    public string $search = '';

    protected $queryString = [
        'monthFilter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    protected function rules(): array
    {
        return [
            'signInAt' => ['required', 'date'],
            'signOutAt' => ['nullable', 'date'],
        ];
    }

    public function mount(): void
    {
        $nowBst = now($this->bstTimezone());
        $this->signInAt = $nowBst->format('Y-m-d\TH:i');
        $this->monthFilter = $nowBst->format('Y-m');
    }

    public function render()
    {
        $appTimezone = config('app.timezone');
        $entries = ManagementEntry::query()
            ->with('user')
            ->when($this->monthFilter, function ($query) use ($appTimezone) {
                $start = Carbon::parse($this->monthFilter . '-01', $this->bstTimezone())->timezone($appTimezone)->startOfDay();
                $end = $start->copy()->endOfMonth();
                $query->whereBetween('sign_in_at', [$start, $end]);
            })
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('sign_in_at')
            ->paginate(10);

        return view('livewire.management.management-entry-board', [
            'entries' => $entries,
            'canCreate' => $this->userIsInstructor(),
            'isReadOnlyViewer' => $this->userIsViewer(),
            'timezoneLabel' => $this->bstTimezone(),
        ]);
    }

    public function save(): void
    {
        if (! $this->userIsInstructor()) {
            abort(403, 'Only instructors can record entry times.');
        }

        $data = $this->validate();

        $signIn = Carbon::parse($data['signInAt'], $this->bstTimezone())->timezone(config('app.timezone'));
        $signOut = $data['signOutAt']
            ? Carbon::parse($data['signOutAt'], $this->bstTimezone())->timezone(config('app.timezone'))
            : null;

        if ($signOut && $signOut->lte($signIn)) {
            $this->addError('signOutAt', 'Sign out must be after sign in.');
            return;
        }

        ManagementEntry::create([
            'user_id' => auth()->id(),
            'sign_in_at' => $signIn,
            'sign_out_at' => $signOut,
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: 'Entry recorded.');
    }

    protected function resetForm(): void
    {
        $nowBst = now($this->bstTimezone());
        $this->signInAt = $nowBst->format('Y-m-d\TH:i');
        $this->signOutAt = '';
    }

    protected function userIsInstructor(): bool
    {
        return auth()->user()?->role === 'instructor';
    }

    protected function userIsViewer(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'director'], true);
    }

    protected function bstTimezone(): string
    {
        return 'Asia/Dhaka'; // BST (Bangladesh Standard Time)
    }
}
