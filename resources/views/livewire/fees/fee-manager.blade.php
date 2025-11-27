<div class="space-y-6">
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-sm text-gray-500">Outstanding Fees</div>
            <div class="text-3xl font-bold text-red-600 mt-2">৳ {{ number_format(max($totals['due'], 0), 2) }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-sm text-gray-500">Total Collected</div>
            <div class="text-3xl font-bold text-green-600 mt-2">৳ {{ number_format($totals['collected'], 2) }}</div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">{{ $editingInvoiceId ? 'Edit Invoice' : 'Create Invoice' }}</h3>
                @if ($editingInvoiceId)
                    <x-secondary-button wire:click="cancelInvoiceEdit" type="button">Cancel Edit</x-secondary-button>
                @endif
            </div>
            <div>
                <x-input-label value="Search Student" />
                <x-text-input type="text" wire:model.live.debounce.300ms="invoiceStudentSearch" class="mt-1 block w-full" placeholder="Start typing a name" />
            </div>
            <div>
                <x-input-label value="Student" />
                <select wire:model.defer="invoiceForm.student_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Select student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->phone_number }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('invoiceForm.student_id')" class="mt-1" />
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <x-input-label value="Billing Month" />
                    <x-text-input type="month" wire:model.defer="invoiceForm.billing_month" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('invoiceForm.billing_month')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Amount Due (৳)" />
                    <x-text-input type="number" step="0.01" wire:model.defer="invoiceForm.amount_due" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('invoiceForm.amount_due')" class="mt-1" />
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <x-input-label value="Due Date" />
                    <x-text-input type="date" wire:model.defer="invoiceForm.due_date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('invoiceForm.due_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Notes" />
                    <x-text-input type="text" wire:model.defer="invoiceForm.notes" class="mt-1 block w-full" />
                </div>
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="createInvoice">
                    Save Invoice
                </x-primary-button>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-4 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">
                    {{ $editingPaymentId ? 'Edit Payment' : 'Record Payment' }}
                </h3>
                @if ($editingPaymentId)
                    <x-secondary-button type="button" wire:click="cancelPaymentEdit">Cancel Edit</x-secondary-button>
                @endif
            </div>
            @if ($selectedInvoice)
                <div class="text-sm text-gray-600">
                    <span class="font-semibold">Selected Invoice:</span>
                    {{ $selectedInvoice->student->name }} —
                    {{ optional($selectedInvoice->billing_month)->format('M Y') }}
                    <div class="text-xs text-gray-500">
                        Net ৳ {{ number_format($selectedInvoice->amount_due, 2) }}
                        @if ($selectedInvoice->scholarship_amount > 0)
                            • Scholarship ৳ {{ number_format($selectedInvoice->scholarship_amount, 2) }}
                            • Base ৳ {{ number_format($selectedInvoice->gross_amount, 2) }}
                        @endif
                    </div>
                </div>
            @endif
            <div>
                <x-input-label value="Payment Date" />
                <x-text-input type="date" wire:model.defer="paymentForm.payment_date" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('paymentForm.payment_date')" class="mt-1" />
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <x-input-label value="Amount (৳)" />
                    <x-text-input type="number" step="0.01" wire:model.defer="paymentForm.amount" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('paymentForm.amount')" class="mt-1" />
                    <p class="text-xs text-gray-500 mt-1">Use 0 only when scholarship covers the full month.</p>
                </div>
                <div>
                    <x-input-label value="Payment Mode" />
                    <select wire:model.defer="paymentForm.payment_mode" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Select</option>
                        @foreach ($paymentModes as $mode)
                            <option value="{{ $mode }}">{{ $mode }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('paymentForm.payment_mode')" class="mt-1" />
                </div>
            </div>
            <div>
                <x-input-label value="Reference / Notes" />
                <x-text-input type="text" wire:model.defer="paymentForm.reference" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label value="Scholarship (৳)" />
                <x-text-input type="number" step="0.01" wire:model.defer="paymentForm.scholarship_amount" class="mt-1 block w-full" placeholder="0.00" />
                <x-input-error :messages="$errors->get('paymentForm.scholarship_amount')" class="mt-1" />
                <p class="text-xs text-gray-500 mt-1">Applies to this invoice’s month; net due updates automatically.</p>
            </div>
            <div>
                <x-input-label value="Receipt Number" />
                <x-text-input type="text" wire:model.defer="paymentForm.receipt_number" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('paymentForm.receipt_number')" class="mt-1" />
            </div>
            <div class="text-right">
                <x-primary-button type="button" wire:click="savePayment" :disabled="!$paymentForm['fee_invoice_id']">
                    {{ $paymentForm['fee_invoice_id'] ? 'Save Payment' : 'Select Invoice Below' }}
                </x-primary-button>
            </div>

            <div class="pt-4 border-t">
                <h4 class="font-semibold text-gray-800 mb-2 text-sm uppercase tracking-wide">Recent Payments</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-left">Student</th>
                                <th class="px-3 py-2 text-left">Invoice Month</th>
                                <th class="px-3 py-2 text-left">Mode</th>
                                <th class="px-3 py-2 text-left">Receipt #</th>
                                <th class="px-3 py-2 text-left">Amount</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentPayments as $payment)
                                <tr>
                                    <td class="px-3 py-2">{{ optional($payment->payment_date)->format('d M Y') }}</td>
                                    <td class="px-3 py-2">
                                        <button type="button" class="font-semibold text-gray-900 underline" wire:click="showPaymentLog({{ $payment->id }})">
                                            {{ $payment->student->name }}
                                        </button>
                                        <div class="text-xs text-gray-500">
                                            {{ \App\Support\AcademyOptions::classLabel($payment->student->class_level ?? '') }},
                                            {{ \App\Support\AcademyOptions::sectionLabel($payment->student->section ?? '') }}
                                        </div>
                                        @php($invoiceScholarship = optional($payment->invoice)->scholarship_amount ?? 0)
                                        @if ($invoiceScholarship > 0)
                                            <div class="text-xs text-blue-600">
                                                Scholarship ৳ {{ number_format($invoiceScholarship, 2) }}
                                                (Base ৳ {{ number_format(optional($payment->invoice)->gross_amount ?? 0, 2) }})
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ optional(optional($payment->invoice)->billing_month)->format('M Y') ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2">{{ $payment->payment_mode ?? '—' }}</td>
                                    <td class="px-3 py-2">{{ $payment->receipt_number }}</td>
                                    <td class="px-3 py-2 text-green-600 font-semibold">৳ {{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <x-secondary-button type="button" wire:click="editPayment({{ $payment->id }})" class="text-xs">
                                            Edit
                                        </x-secondary-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-4 text-center text-gray-500">No recent payments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 space-y-4">
        <div class="grid md:grid-cols-6 gap-3 items-end">
            <div class="md:w-32">
                <x-input-label value="Class" />
                <select wire:model.live="filterClass" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($classOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:w-40">
                <x-input-label value="Section" />
                <select wire:model.live="filterSection" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($sectionOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:w-32">
                <x-input-label value="Status" />
                <select wire:model.live="filterStatus" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="all">All</option>
                </select>
            </div>
            <div class="md:w-36">
                <x-input-label value="Billing Month" />
                <x-text-input type="month" wire:model.live="filterMonth" class="mt-1 block w-full" />
            </div>
            <div class="md:col-span-2">
                <x-input-label value="Search Invoice" />
                <x-text-input type="text" wire:model.live.debounce.300ms="invoiceStudentSearch" class="mt-1 block w-full" placeholder="Name or phone" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <th class="px-4 py-2">Student</th>
                        <th class="px-4 py-2">Month</th>
                        <th class="px-4 py-2">Amount</th>
                        <th class="px-4 py-2">Paid</th>
                        <th class="px-4 py-2">Due</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Updated</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-semibold text-gray-900">{{ $invoice->student->name }}</div>
                                <div class="text-xs text-gray-500">{{ $invoice->student->phone_number }}</div>
                            </td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}</td>
                            <td class="px-4 py-2">
                                <div>৳ {{ number_format($invoice->amount_due, 2) }}</div>
                                @if ($invoice->scholarship_amount > 0)
                                    <div class="text-xs text-gray-500">
                                        Base ৳ {{ number_format($invoice->gross_amount, 2) }} • Scholarship ৳ {{ number_format($invoice->scholarship_amount, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-green-600">৳ {{ number_format($invoice->amount_paid, 2) }}</td>
                            <td class="px-4 py-2 text-red-600">৳ {{ number_format($invoice->outstanding_amount, 2) }}</td>
                        <td class="px-4 py-2">
                            <span class="px-3 py-1 rounded-full text-xs
                                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : ($invoice->status === 'partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500">
                            {{ optional($invoice->updated_at)->diffForHumans() }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <x-secondary-button type="button" wire:click="preparePayment({{ $invoice->id }})" class="text-xs">
                                Record Payment
                            </x-secondary-button>
                            <x-secondary-button type="button" wire:click="editInvoice({{ $invoice->id }})" class="text-xs">
                                Edit
                            </x-secondary-button>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>

    @if ($showPaymentLogModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 px-3">
            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 w-full max-w-xl p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="font-semibold text-gray-900 text-lg">{{ $paymentLog['student'] ?? 'Payment' }}</div>
                        <div class="text-xs text-gray-500">
                            {{ \App\Support\AcademyOptions::classLabel($paymentLog['class'] ?? '') }}
                            • {{ \App\Support\AcademyOptions::sectionLabel($paymentLog['section'] ?? '') }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (!empty($paymentLog['edited']))
                            <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Edited</span>
                        @endif
                        <x-secondary-button type="button" wire:click="closePaymentLog">Close</x-secondary-button>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div><span class="font-semibold">Invoice Month:</span> {{ $paymentLog['invoice_month'] ?? '—' }}</div>
                    <div><span class="font-semibold">Receipt #:</span> {{ $paymentLog['receipt_number'] ?? '—' }}</div>
                    <div><span class="font-semibold">Payment Date:</span> {{ $paymentLog['payment_date'] ?? '—' }}</div>
                    <div><span class="font-semibold">Mode:</span> {{ $paymentLog['payment_mode'] ?? '—' }}</div>
                    <div>
                        <span class="font-semibold">Amount:</span>
                        @if (!empty($paymentLog['previous_amount']))
                            ৳ {{ number_format($paymentLog['previous_amount'], 2) }} → ৳ {{ number_format($paymentLog['amount'] ?? 0, 2) }}
                        @else
                            ৳ {{ number_format($paymentLog['amount'] ?? 0, 2) }}
                        @endif
                    </div>
                    <div>
                        <span class="font-semibold">Scholarship:</span>
                        @if (!empty($paymentLog['previous_scholarship_amount']))
                            ৳ {{ number_format($paymentLog['previous_scholarship_amount'], 2) }} → ৳ {{ number_format($paymentLog['scholarship'] ?? 0, 2) }}
                        @else
                            ৳ {{ number_format($paymentLog['scholarship'] ?? 0, 2) }}
                        @endif
                    </div>
                    <div><span class="font-semibold">Base Amount:</span> ৳ {{ number_format($paymentLog['base_amount'] ?? 0, 2) }}</div>
                    <div><span class="font-semibold">Reference:</span> {{ $paymentLog['reference'] ?? '—' }}</div>
                    <div><span class="font-semibold">Created:</span> {{ $paymentLog['created_at'] ?? '—' }}</div>
                    <div><span class="font-semibold">Last Updated:</span> {{ $paymentLog['updated_at'] ?? '—' }}</div>
                </div>
            </div>
        </div>
    @endif
</div>
