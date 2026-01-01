<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class AuditLogBoard extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $actionFilter = '';
    public string $userFilter = 'all';
    public string $dateStart = '';
    public string $dateEnd = '';
    public $importFile;

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
            'canManage' => $this->canManage(),
        ]);
    }

    public function exportCsv()
    {
        $this->authorizeManage();

        $filename = 'audit-logs-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['id', 'user_id', 'user_email', 'action', 'model_type', 'model_id', 'description', 'meta', 'created_at', 'updated_at']);

            AuditLog::with('user')
                ->orderByDesc('created_at')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->user_id,
                            $log->user?->email,
                            $log->action,
                            $log->model_type,
                            $log->model_id,
                            $log->description,
                            $log->meta ? json_encode($log->meta) : '',
                            optional($log->created_at)->toDateTimeString(),
                            optional($log->updated_at)->toDateTimeString(),
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
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }
        $header = $header ? array_map(fn ($h) => strtolower(trim($h)), $header) : [];
        $expected = ['id', 'user_id', 'user_email', 'action', 'model_type', 'model_id', 'description', 'meta', 'created_at', 'updated_at'];
        $hasHeader = count(array_intersect($header, $expected)) >= 2;

        $emailLookup = [];
        foreach (User::pluck('id', 'email') as $email => $id) {
            $emailLookup[strtolower($email)] = $id;
        }

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

            $action = trim($record['action'] ?? '');
            if ($action === '') {
                continue;
            }

            $userId = $this->resolveUserId($record, $emailLookup);
            $modelType = trim($record['model_type'] ?? '');
            $modelId = is_numeric($record['model_id'] ?? null) ? (int) $record['model_id'] : null;
            $description = trim($record['description'] ?? '');

            $meta = null;
            $metaRaw = $record['meta'] ?? null;
            if ($metaRaw !== null && $metaRaw !== '') {
                $decoded = json_decode($metaRaw, true);
                $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : ['raw' => $metaRaw];
            }

            $createdAt = $this->parseDate($record['created_at'] ?? null) ?? now();
            $updatedAt = $this->parseDate($record['updated_at'] ?? null) ?? $createdAt;

            $attributes = [];
            $idValue = trim((string) ($record['id'] ?? ''));
            if ($idValue !== '' && ctype_digit($idValue)) {
                $attributes['id'] = (int) $idValue;
            }

            $values = [
                'user_id' => $userId,
                'action' => $action,
                'model_type' => $modelType ?: null,
                'model_id' => $modelId,
                'description' => $description ?: null,
                'meta' => $meta,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];

            AuditLog::unguarded(function () use ($attributes, $values) {
                if ($attributes) {
                    AuditLog::updateOrCreate($attributes, $values);
                } else {
                    AuditLog::create($values);
                }
            });

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
            return;
        }

        $this->dispatch('notify', message: "Import complete. {$imported} audit log record(s) processed.");
    }

    protected function resolveUserId(array $record, array $emailLookup): ?int
    {
        $rawId = trim((string) ($record['user_id'] ?? ''));
        if ($rawId !== '' && ctype_digit($rawId)) {
            return (int) $rawId;
        }

        $email = strtolower(trim($record['user_email'] ?? ''));
        if ($email !== '' && isset($emailLookup[$email])) {
            return (int) $emailLookup[$email];
        }

        return null;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function authorizeManage(): void
    {
        if (! $this->canManage()) {
            abort(403);
        }
    }

    protected function canManage(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'director'], true);
    }
}
