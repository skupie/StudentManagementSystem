<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <x-input-label value="Holiday Date" />
                <x-text-input type="date" wire:model.defer="holidayDate" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('holidayDate')" class="mt-1" />
            </div>
            <div>
                <x-input-label value="Reason" />
                <x-text-input type="text" wire:model.defer="reason" class="mt-1 block w-full" placeholder="Reason (optional)" />
                <x-input-error :messages="$errors->get('reason')" class="mt-1" />
            </div>
        </div>
        <div class="text-right">
            <x-primary-button type="button" wire:click="save">
                Save Holiday
            </x-primary-button>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Holidays</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2 text-left">Date</th>
                        <th class="px-4 py-2 text-left">Reason</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($holidays as $holiday)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ optional($holiday->holiday_date)->format('d M Y') }}</td>
                            <td class="px-4 py-2">{{ $holiday->reason ?: 'N/A' }}</td>
                            <td class="px-4 py-2 text-right">
                                <x-danger-button type="button" wire:click="delete({{ $holiday->id }})" class="text-xs">
                                    Delete
                                </x-danger-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">No holidays added.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $holidays->links() }}
        </div>
    </div>
</div>
