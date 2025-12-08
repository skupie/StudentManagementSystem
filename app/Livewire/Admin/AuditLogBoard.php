<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogBoard extends Component
{
    use WithPagination;

    public string $actionFilter = '';
    public string $userFilter = 'all';
    public string $dateStart = '';
    public string $dateEnd = '';

    protected $paginationTheme = 'tailwind';

    public function updating($field): void
    {
        if (in_array($field, ['actionFilter', 'userFilter', 'dateStart', 'dateEnd'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->when($this->actionFilter, fn ($q) => $q->where('action', 'like', '%' . $this->actionFilter . '%'))
            ->when($this->userFilter !== 'all', fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->dateStart, fn ($q) => $q->whereDate('created_at', '>=', Carbon::parse($this->dateStart)))
            ->when($this->dateEnd, fn ($q) => $q->whereDate('created_at', '<=', Carbon::parse($this->dateEnd)));

        $logs = $query->paginate(15);
        $users = User::orderBy('name')->get();

        return view('livewire.admin.audit-log-board', [
            'logs' => $logs,
            'users' => $users,
        ]);
    }
}
