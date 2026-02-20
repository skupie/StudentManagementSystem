<div class="space-y-6">
    @if (! $teacher)
        <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
            No teacher profile is linked with your account. Contact admin to link your login credentials.
        </div>
    @else
        <div class="bg-white shadow rounded-lg p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Transaction Log</h3>
                    <p class="text-sm text-gray-500">{{ $teacher->name }}</p>
                </div>
                <div class="text-sm text-gray-600">
                    Total: <span class="font-semibold text-gray-900">Tk {{ number_format($totalAmount, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <div class="max-w-xs">
                <x-input-label value="Month Filter" />
                <select wire:model.live="monthFilter" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All Months</option>
                    @foreach ($monthOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-3 py-2">Pay Month</th>
                            <th class="px-3 py-2">Classes</th>
                            <th class="px-3 py-2">Amount</th>
                            <th class="px-3 py-2">Date of Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-3 py-2">{{ optional($payment->payout_month)->format('M Y') ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $payment->class_count }}</td>
                                <td class="px-3 py-2 font-semibold">Tk {{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-3 py-2">
                                    {{ optional($payment->logged_at ?: $payment->created_at)->format('d M Y') ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-5 text-center text-gray-500">
                                    No ledger transactions found for your account.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    @endif
</div>
