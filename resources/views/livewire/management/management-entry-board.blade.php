<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="font-semibold text-gray-800">Management Entry Log</div>
            <div class="text-sm text-gray-600">Times are interpreted and shown in BST ({{ $timezoneLabel }}).</div>
            <div class="text-sm text-gray-500">Instructors can record entries. Directors and Admins have read-only access.</div>
        </div>
        <div class="text-sm text-gray-500">Today: {{ now($timezoneLabel)->format('d M Y, h:i A') }}</div>
    </div>

    @if ($canCreate)
        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <h3 class="font-semibold text-gray-800">Record Entry</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="Sign In (BST)" />
                    <x-text-input type="datetime-local" wire:model.defer="signInAt" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('signInAt')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Sign Out (BST)" />
                    <x-text-input type="datetime-local" wire:model.defer="signOutAt" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('signOutAt')" class="mt-1" />
                </div>
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="save">
                    Save Entry
                </x-primary-button>
            </div>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-800">Entry History</h3>
                @if ($isReadOnlyViewer)
                    <p class="text-sm text-gray-500">Admin/Director can view entries only.</p>
                @elseif (! $canCreate)
                    <p class="text-sm text-gray-500">Entries are read-only for your role.</p>
                @endif
            </div>
            <div class="flex flex-col md:flex-row gap-3">
                <div>
                    <x-input-label value="Filter Month" />
                    <x-text-input type="month" wire:model.live="monthFilter" class="mt-1 block w-full md:w-44" />
                </div>
                <div>
                    <x-input-label value="Search by Name" />
                    <x-text-input type="text" wire:model.live.debounce.300ms="search" placeholder="Name" class="mt-1 block w-full md:w-52" />
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Sign In (BST)</th>
                        <th class="px-4 py-2">Sign Out (BST)</th>
                        <th class="px-4 py-2">Recorded</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $entry->user?->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $entry->user?->role ? ucfirst($entry->user->role) : '' }}</div>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800">
                                {{ optional($entry->sign_in_at)?->timezone($timezoneLabel)->format('d M Y, h:i A') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800">
                                {{ $entry->sign_out_at ? $entry->sign_out_at->timezone($timezoneLabel)->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">
                                {{ optional($entry->created_at)?->timezone($timezoneLabel)->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                No entries found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $entries->links() }}
    </div>
</div>
