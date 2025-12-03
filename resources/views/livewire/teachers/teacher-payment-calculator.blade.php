<div class="bg-white shadow rounded-lg p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Teacher Payment Calculator</h2>
            <p class="text-sm text-gray-600">Enter total classes for each teacher; payments will be logged to the ledger as expenses.</p>
        </div>
        <div class="flex flex-col md:flex-row gap-3 md:items-end">
            <div>
                <x-input-label value="Expense Date" />
                <x-text-input type="date" wire:model.defer="expenseDate" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="Note (optional)" />
                <x-text-input type="text" wire:model.defer="note" class="mt-1 block w-full" placeholder="e.g., Weekly payouts" />
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-4 py-2">Teacher</th>
                    <th class="px-4 py-2">Rate (৳/class)</th>
                    <th class="px-4 py-2">Total Classes</th>
                    <th class="px-4 py-2 text-right">Total Payment (৳)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($teachers as $teacher)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="font-semibold text-gray-900">{{ $teacher->name }}</div>
                            <div class="text-xs text-gray-500">{{ $teacher->subjects ? implode(', ', $teacher->subjects) : $teacher->subject }}</div>
                        </td>
                        <td class="px-4 py-2">৳ {{ number_format($teacher->payment ?? 0, 2) }}</td>
                        <td class="px-4 py-2">
                            <x-text-input
                                type="number"
                                min="0"
                                step="1"
                                wire:model.defer="classCounts.{{ $teacher->id }}"
                                class="mt-1 block w-24"
                            />
                        </td>
                        <td class="px-4 py-2 text-right font-semibold">
                            ৳ {{ number_format($teacher->calculated_total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">No active teachers found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2" colspan="3">Grand Total</td>
                    <td class="px-4 py-2 text-right">৳ {{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="flex justify-end gap-3">
        <x-secondary-button type="button" wire:click="$refresh">Refresh</x-secondary-button>
        <x-primary-button type="button" wire:click="save">
            Log to Ledger
        </x-primary-button>
    </div>

    @if ($saved)
        <div class="p-3 bg-green-50 border border-green-200 rounded-md text-green-800">
            Teacher payments have been recorded to the ledger.
        </div>
    @endif
</div>
