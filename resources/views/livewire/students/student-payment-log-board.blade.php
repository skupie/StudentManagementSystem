<div class="space-y-6">
    @if (! $student)
        <div class="bg-white shadow rounded-lg p-6 text-sm text-red-700 border border-red-100">
            No student profile is linked with your account. Contact admin to link your login credentials.
        </div>
    @else
        <div class="bg-white shadow rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800">Payment Logs</h3>
            <p class="text-sm text-gray-500">{{ $student->name }}</p>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-3">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Billing Month</th>
                            <th class="px-3 py-2">Amount</th>
                            <th class="px-3 py-2">Receipt</th>
                            <th class="px-3 py-2">Payment Mode</th>
                            <th class="px-3 py-2">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-3 py-2">{{ optional($payment->payment_date)->format('d M Y') }}</td>
                                <td class="px-3 py-2">{{ optional($payment->invoice?->billing_month)->format('M Y') ?: '-' }}</td>
                                <td class="px-3 py-2">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="px-3 py-2">{{ $payment->receipt_number ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $payment->payment_mode ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $payment->reference ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">No payment logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        </div>
    @endif
</div>
