<div
    x-data="{ show: false, message: '', timer: null, tone: 'success', title: 'Success' }"
    x-on:notify.window="
        message = $event.detail?.message || 'Saved successfully.';
        if (message.toLowerCase().includes('invoice')) { tone = 'info'; title = 'Invoice'; }
        else if (message.toLowerCase().includes('payment')) { tone = 'success'; title = 'Payment'; }
        else { tone = 'success'; title = 'Success'; }
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => { show = false; }, 2400);
    "
    class="space-y-6"
>
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="fixed inset-0 z-[1100] flex items-center justify-center pointer-events-none"
    >
        <div class="pointer-events-auto rounded-xl shadow-2xl px-5 py-4 flex items-center gap-3 border"
             :style="tone === 'info'
                ? 'background: #eff6ff; border-color: #93c5fd;'
                : 'background: #ecfdf5; border-color: #6ee7b7;'">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white shadow"
                 :style="tone === 'info'
                    ? 'background: linear-gradient(135deg, #60a5fa, #2563eb);'
                    : 'background: linear-gradient(135deg, #34d399, #059669);'">
                <svg viewBox="0 0 24 24" class="w-5 h-5">
                    <path fill="currentColor" d="M9.2 16.2l-3.4-3.4 1.4-1.4 2 2 6-6 1.4 1.4-7.4 7.4z"/>
                </svg>
            </div>
            <div>
                <div class="text-sm font-semibold"
                     :style="tone === 'info' ? 'color: #1e3a8a;' : 'color: #065f46;'"
                     x-text="title"></div>
                <div class="text-xs text-gray-600" x-text="message"></div>
            </div>
        </div>
    </div>
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
                        <option value="{{ $student->id }}">
                            {{ $student->name }} ({{ $student->phone_number }})
                            — {{ \App\Support\AcademyOptions::classLabel($student->class_level ?? '') }},
                            {{ \App\Support\AcademyOptions::sectionLabel($student->section ?? '') }}
                        </option>
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
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-2">
                    <x-text-input type="text" wire:model.live.debounce.300ms="recentSearch" class="mt-1 block w-full md:w-64" placeholder="Search by name or receipt" />
                    <div class="text-xs text-gray-500">{{ $recentPayments->total() }} payments</div>
                </div>
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
                                    @if ($payment['type'] === 'fee')
                                        @php($item = $payment['model'])
                                        <td class="px-3 py-2">{{ optional($item->payment_date)->format('d M Y') }}</td>
                                        <td class="px-3 py-2">
                                            <button type="button" class="font-semibold text-gray-900 underline" wire:click="showPaymentLog({{ $item->id }})">
                                                {{ $item->student->name }}
                                            </button>
                                            <div class="text-xs text-gray-500">
                                                {{ \App\Support\AcademyOptions::classLabel($item->student->class_level ?? '') }},
                                                {{ \App\Support\AcademyOptions::sectionLabel($item->student->section ?? '') }}
                                            </div>
                                            @php($invoiceScholarship = optional($item->invoice)->scholarship_amount ?? 0)
                                            @if ($invoiceScholarship > 0)
                                                <div class="text-xs text-blue-600">
                                                    Scholarship ৳ {{ number_format($invoiceScholarship, 2) }}
                                                    (Base ৳ {{ number_format(optional($item->invoice)->gross_amount ?? 0, 2) }})
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ optional(optional($item->invoice)->billing_month)->format('M Y') ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">{{ $item->payment_mode ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $item->receipt_number }}</td>
                                        <td class="px-3 py-2 text-green-600 font-semibold">৳ {{ number_format($item->amount, 2) }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <x-secondary-button type="button" wire:click="editPayment({{ $item->id }})" class="text-xs">
                                                Edit
                                            </x-secondary-button>
                                        </td>
                                    @else
                                        @php($item = $payment['model'])
                                        <td class="px-3 py-2">{{ optional($item->income_date)->format('d M Y') }}</td>
                                        <td class="px-3 py-2">
                                            @php($studentName = 'Student')
                                            @php($studentClass = '')
                                            @php($studentSection = '')
                                            @if (!empty($item->description) && preg_match('/^Admission fee for\s*(.+?)\s*-\s*(.+?)\s*-\s*(.+?)\s*$/i', $item->description, $matches))
                                                @php($studentName = trim($matches[1]))
                                                @php($studentClass = trim($matches[2]))
                                                @php($studentSection = trim($matches[3]))
                                            @elseif (!empty($item->description) && preg_match('/^Admission fee for\s*(.+?)(?:\s*-|$)/i', $item->description, $matches))
                                                @php($studentName = trim($matches[1]))
                                            @endif
                                            <div class="font-semibold text-gray-900">{{ $studentName }}</div>
                                            <div class="text-xs text-gray-500">
                                                @if ($studentClass !== '' || $studentSection !== '')
                                                    {{ $studentClass }}{{ $studentClass && $studentSection ? ', ' : '' }}{{ $studentSection }}
                                                @else
                                                    Admission Fee
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2">Admission</td>
                                        <td class="px-3 py-2">—</td>
                                        <td class="px-3 py-2">{{ $item->receipt_number ?? '—' }}</td>
                                        <td class="px-3 py-2 text-green-600 font-semibold">৳ {{ number_format($item->amount, 2) }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <span class="text-xs text-gray-400">N/A</span>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-4 text-center text-gray-500">No recent payments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">
                    {{ $recentPayments->links() }}
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
                                <div class="text-xs text-gray-500">
                                    {{ \App\Support\AcademyOptions::classLabel($invoice->student->class_level ?? '') }},
                                    {{ \App\Support\AcademyOptions::sectionLabel($invoice->student->section ?? '') }}
                                </div>
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

    <style>
        [x-cloak] { display: none !important; }
    </style>
</div>
