<?php

namespace App\Livewire\Management;

use App\Models\ManagementEntry;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ManagementEntryBoard extends Component
{
    use WithPagination;

    public string $entryName = '';
    public string $signInAt = '';
    public string $signOutAt = '';
    public string $monthFilter = '';
    public string $search = '';
    public ?int $editingId = null;

    protected $queryString = [
        'monthFilter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    protected function rules(): array
    {
        return [
            'signInAt' => ['required', 'date'],
            'signOutAt' => ['nullable', 'date'],
            'entryName' => ['required', 'string', 'max:255'],
        ];
    }

    public function mount(): void
    {
        $nowBst = now($this->bstTimezone());
        $this->entryName = auth()->user()?->name ?? '';
        $this->signInAt = $nowBst->format('Y-m-d\TH:i');
        $this->monthFilter = $nowBst->format('Y-m');
    }

    public function render()
    {
        $appTimezone = config('app.timezone');
        $entries = ManagementEntry::query()
            ->with('user')
            ->when($this->monthFilter, function ($query) use ($appTimezone) {
                $start = Carbon::createFromFormat('Y-m', $this->monthFilter, $this->bstTimezone())
                    ->startOfMonth()
                    ->startOfDay()
                    ->timezone($appTimezone);
                $end = Carbon::createFromFormat('Y-m', $this->monthFilter, $this->bstTimezone())
                    ->endOfMonth()
                    ->endOfDay()
                    ->timezone($appTimezone);
                $query->whereBetween('sign_in_at', [$start, $end]);
            })
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('entry_name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderByDesc('sign_in_at')
            ->paginate(10);

        return view('livewire.management.management-entry-board', [
            'entries' => $entries,
            'canCreate' => $this->userIsInstructor(),
            'isReadOnlyViewer' => $this->userIsViewer(),
            'timezoneLabel' => $this->bstTimezone(),
            'canEdit' => $this->userIsInstructor(),
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

        if ($this->editingId) {
            ManagementEntry::whereKey($this->editingId)->update([
                'entry_name' => $data['entryName'],
                'sign_in_at' => $signIn,
                'sign_out_at' => $signOut,
            ]);
        } else {
            ManagementEntry::create([
                'user_id' => auth()->id(),
                'entry_name' => $data['entryName'],
                'sign_in_at' => $signIn,
                'sign_out_at' => $signOut,
            ]);
        }

        $this->resetForm();
        $this->dispatch('notify', message: 'Entry recorded.');
    }

    public function startEdit(int $entryId): void
    {
        if (! $this->userIsInstructor()) {
            return;
        }

        $entry = ManagementEntry::find($entryId);
        if (! $entry) {
            return;
        }

        $this->editingId = $entry->id;
        $this->entryName = $entry->entry_name ?: ($entry->user?->name ?? '');
        $this->signInAt = $entry->sign_in_at
            ? $entry->sign_in_at->timezone($this->bstTimezone())->format('Y-m-d\TH:i')
            : '';
        $this->signOutAt = $entry->sign_out_at
            ? $entry->sign_out_at->timezone($this->bstTimezone())->format('Y-m-d\TH:i')
            : '';
    }

    protected function resetForm(): void
    {
        $nowBst = now($this->bstTimezone());
        $this->entryName = auth()->user()?->name ?? '';
        $this->signInAt = $nowBst->format('Y-m-d\TH:i');
        $this->signOutAt = '';
        $this->editingId = null;
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
