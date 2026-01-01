<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Audit Logs</h2>
                <p class="text-sm text-gray-500">Sensitive actions (routines, payouts, deletions) with filters.</p>
            </div>
            <div class="grid md:grid-cols-4 gap-3 w-full md:w-auto">
                <div>
                    <x-input-label value="Action" />
                    <x-text-input type="text" wire:model.live.debounce.300ms="actionFilter" class="mt-1 block w-full" placeholder="e.g. routine" />
                </div>
                <div>
                    <x-input-label value="User" />
                    <select wire:model.live="userFilter" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="all">All</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Date From" />
                    <x-text-input type="date" wire:model.live="dateStart" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label value="Date To" />
                    <x-text-input type="date" wire:model.live="dateEnd" class="mt-1 block w-full" />
                </div>
            </div>
        </div>
    </div>

    @if ($canManage)
        <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h4 class="font-semibold text-gray-800">CSV Import / Export</h4>
                <p class="text-sm text-gray-500">Export audit logs or import a CSV backup.</p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 md:items-end w-full md:w-auto">
                <div>
                    <x-input-label value="Import CSV" />
                    <input type="file" wire:model="importFile" accept=".csv,text/csv" class="mt-1 block w-full text-sm">
                    <x-input-error :messages="$errors->get('importFile')" class="mt-1" />
                </div>
                <div class="flex gap-2">
                    <x-secondary-button type="button" wire:click="exportCsv">
                        Export CSV
                    </x-secondary-button>
                    <x-primary-button type="button" wire:click="importCsv" wire:loading.attr="disabled">
                        Import CSV
                    </x-primary-button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-4">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">Date (BST)</th>
                        <th class="px-3 py-2 text-left">User</th>
                        <th class="px-3 py-2 text-left">Action</th>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-left">Meta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-xs text-gray-700">
                                {{ optional($log->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-900">{{ $log->user?->name ?? 'System' }}</div>
                                <div class="text-xs text-gray-500">{{ $log->user?->email }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $log->action }}</td>
                            <td class="px-3 py-2">{{ $log->description ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600 whitespace-pre-line">
                                @if ($log->meta)
                                    @foreach ($log->meta as $k => $v)
                                        {{ $k }}: {{ is_array($v) ? json_encode($v) : $v }}
                                        @if (! $loop->last)
                                            {!! '<br>' !!}
                                        @endif
                                    @endforeach
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-gray-500">No logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
