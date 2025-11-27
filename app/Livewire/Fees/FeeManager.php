<?php

namespace App\Livewire\Fees;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Support\AcademyOptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class FeeManager extends Component
{
    use WithPagination;

    public string $filterClass = 'all';
    public string $filterSection = 'all';
    public string $filterStatus = 'pending';
    public string $filterMonth = '';
    public string $invoiceStudentSearch = '';

    public array $invoiceForm = [
        'student_id' => '',
        'billing_month' => '',
        'amount_due' => '',
        'due_date' => '',
        'notes' => '',
    ];
    public ?int $editingInvoiceId = null;

    public array $paymentForm = [
        'fee_invoice_id' => '',
        'amount' => '',
        'payment_date' => '',
        'payment_mode' => '',
        'reference' => '',
        'receipt_number' => '',
        'scholarship_amount' => '',
    ];

    public ?int $payingInvoiceId = null;
    public ?int $editingPaymentId = null;

    protected function rules(): array
    {
        return [
            'invoiceForm.student_id' => ['required', 'exists:students,id'],
            'invoiceForm.billing_month' => ['required', 'date'],
            'invoiceForm.amount_due' => ['required', 'numeric', 'min:0'],
            'invoiceForm.due_date' => ['nullable', 'date'],
            'invoiceForm.notes' => ['nullable', 'string'],
            'paymentForm.fee_invoice_id' => ['nullable', 'exists:fee_invoices,id'],
            'paymentForm.amount' => ['nullable', 'numeric', 'min:0'],
            'paymentForm.payment_date' => ['nullable', 'date'],
            'paymentForm.payment_mode' => ['nullable', 'string', 'max:50'],
            'paymentForm.reference' => ['nullable', 'string', 'max:100'],
            'paymentForm.receipt_number' => ['nullable', 'string', 'max:100'],
            'paymentForm.scholarship_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function mount(): void
    {
        $this->invoiceForm['billing_month'] = now()->format('Y-m-01');
        $this->invoiceForm['due_date'] = now()->endOfMonth()->format('Y-m-d');
        $this->paymentForm['payment_date'] = now()->format('Y-m-d');
    }

    public function render()
    {
        $students = Student::query()
            ->when($this->invoiceStudentSearch, fn ($q) => $q->where('name', 'like', '%' . $this->invoiceStudentSearch . '%'))
            ->orderBy('name')
            ->get();

        $recentPayments = FeePayment::with(['student', 'invoice.student'])
            ->latest('payment_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $invoices = FeeInvoice::query()
            ->with('student')
            ->when($this->filterClass !== 'all', fn ($q) => $q->whereHas('student', fn ($sub) => $sub->where('class_level', $this->filterClass)))
            ->when($this->filterSection !== 'all', fn ($q) => $q->whereHas('student', fn ($sub) => $sub->where('section', $this->filterSection)))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->invoiceStudentSearch, fn ($q) => $q->whereHas('student', function ($sub) {
                $sub->where('name', 'like', '%' . $this->invoiceStudentSearch . '%')
                    ->orWhere('phone_number', 'like', '%' . $this->invoiceStudentSearch . '%');
            }))
            ->when($this->filterMonth, fn ($q) => $q->whereRaw("DATE_FORMAT(billing_month, '%Y-%m') = ?", [$this->filterMonth]))
            ->latest('billing_month')
            ->paginate(10);

        $selectedInvoice = null;
        if ($this->paymentForm['fee_invoice_id']) {
            $selectedInvoice = FeeInvoice::with('student')->find($this->paymentForm['fee_invoice_id']);
        }

        $totalDue = 0;
        Student::chunkById(200, function ($students) use (&$totalDue) {
            foreach ($students as $student) {
                $totalDue += $student->dueSummary()['amount'];
            }
        });

        $totals = [
            'due' => $totalDue,
            'collected' => FeePayment::sum('amount'),
        ];

        return view('livewire.fees.fee-manager', [
            'students' => $students,
            'invoices' => $invoices,
            'classOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
            'paymentModes' => AcademyOptions::paymentModes(),
            'totals' => $totals,
            'recentPayments' => $recentPayments,
            'selectedInvoice' => $selectedInvoice,
        ]);
    }

    public function updatedInvoiceStudentSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClass(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSection(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMonth(): void
    {
        $this->resetPage();
    }

    public function createInvoice(): void
    {
        $data = $this->validate([
            'invoiceForm.student_id' => ['required', 'exists:students,id'],
            'invoiceForm.billing_month' => ['required', 'date'],
            'invoiceForm.amount_due' => ['required', 'numeric', 'min:0'],
            'invoiceForm.due_date' => ['nullable', 'date'],
            'invoiceForm.notes' => ['nullable', 'string'],
        ])['invoiceForm'];

        $month = Carbon::parse($data['billing_month'])->startOfMonth();
        $baseAmount = round((float) $data['amount_due'], 2);

        if ($this->editingInvoiceId) {
            $invoice = FeeInvoice::findOrFail($this->editingInvoiceId);
            $invoice->update([
                'student_id' => $data['student_id'],
                'billing_month' => $month,
                'due_date' => $data['due_date'],
                'amount_due' => $baseAmount,
                'scholarship_amount' => 0,
                'was_active' => true,
                'manual_override' => true,
                'notes' => $data['notes'],
                'status' => 'pending',
            ]);
            $invoice->amount_paid = min($invoice->amount_paid ?? 0, $invoice->amount_due);
            $invoice->status = $invoice->amount_paid >= $invoice->amount_due
                ? 'paid'
                : ($invoice->amount_paid > 0 ? 'partial' : 'pending');
            $invoice->save();
        } else {
            $invoice = FeeInvoice::updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'billing_month' => $month,
                ],
                [
                    'due_date' => $data['due_date'],
                    'amount_due' => $baseAmount,
                    'scholarship_amount' => 0,
                    'was_active' => true,
                    'manual_override' => true,
                    'notes' => $data['notes'],
                    'amount_paid' => 0,
                    'status' => 'pending',
                ]
            );
        }

        $this->resetInvoiceForm($invoice->student_id);
        $this->resetPage();
    }

    public function editInvoice(int $invoiceId): void
    {
        $invoice = FeeInvoice::findOrFail($invoiceId);
        $this->editingInvoiceId = $invoice->id;
        $this->invoiceForm = [
            'student_id' => (string) $invoice->student_id,
            'billing_month' => Carbon::parse($invoice->billing_month)->format('Y-m-d'),
            'amount_due' => $invoice->gross_amount,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'notes' => $invoice->notes,
        ];
    }

    public function cancelInvoiceEdit(): void
    {
        $this->resetInvoiceForm();
    }

    public function preparePayment(int $invoiceId): void
    {
        $invoice = FeeInvoice::findOrFail($invoiceId);
        $this->editingPaymentId = null;
        $this->payingInvoiceId = $invoice->id;
        $this->paymentForm = [
            'fee_invoice_id' => $invoice->id,
            'amount' => max(0, $invoice->amount_due - $invoice->amount_paid),
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => $invoice->payment_mode_last,
            'reference' => '',
            'receipt_number' => '',
            'scholarship_amount' => $invoice->scholarship_amount,
        ];
    }

    public function savePayment(): void
    {
        if (! $this->paymentForm['fee_invoice_id']) {
            return;
        }

        $this->validate([
            'paymentForm.fee_invoice_id' => ['required', 'exists:fee_invoices,id'],
            'paymentForm.amount' => ['required', 'numeric', 'min:0'],
            'paymentForm.payment_date' => ['required', 'date'],
            'paymentForm.payment_mode' => ['nullable', 'string', 'max:50'],
            'paymentForm.reference' => ['nullable', 'string', 'max:100'],
            'paymentForm.receipt_number' => ['required', 'string', 'max:100'],
            'paymentForm.scholarship_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () {
            $targetInvoice = FeeInvoice::lockForUpdate()->findOrFail($this->paymentForm['fee_invoice_id']);
            $baseAmount = (float) $targetInvoice->gross_amount;
            $scholarship = round((float) ($this->paymentForm['scholarship_amount'] ?? 0), 2);
            $paymentAmount = round((float) ($this->paymentForm['amount'] ?? 0), 2);
            $shouldRecordPayment = $paymentAmount > 0 || $scholarship > 0;

            if ($scholarship > $baseAmount) {
                throw ValidationException::withMessages([
                    'paymentForm.scholarship_amount' => 'Scholarship cannot exceed the month amount.',
                ]);
            }

            $targetInvoice->scholarship_amount = $scholarship;
            $targetInvoice->amount_due = round(max(0, $baseAmount - $scholarship), 2);
            $targetInvoice->save();

            if ($targetInvoice->amount_due > 0 && $paymentAmount < 0.01) {
                throw ValidationException::withMessages([
                    'paymentForm.amount' => 'Provide at least ৳0.01 when there is an outstanding balance.',
                ]);
            }

            if ($this->editingPaymentId) {
                $payment = FeePayment::lockForUpdate()->findOrFail($this->editingPaymentId);
                $previousInvoice = FeeInvoice::lockForUpdate()->findOrFail($payment->fee_invoice_id);

                $payment->update([
                    'fee_invoice_id' => $targetInvoice->id,
                    'student_id' => $targetInvoice->student_id,
                    'amount' => $paymentAmount,
                    'payment_date' => $this->paymentForm['payment_date'],
                    'payment_mode' => $this->paymentForm['payment_mode'],
                    'reference' => $this->paymentForm['reference'],
                    'receipt_number' => $this->paymentForm['receipt_number'],
                ]);

                $this->refreshInvoicePaymentSummary($previousInvoice);
                $this->refreshInvoicePaymentSummary($targetInvoice);
            } else {
                if ($shouldRecordPayment) {
                    FeePayment::create([
                        'fee_invoice_id' => $targetInvoice->id,
                        'student_id' => $targetInvoice->student_id,
                        'amount' => $paymentAmount,
                        'payment_date' => $this->paymentForm['payment_date'],
                        'payment_mode' => $this->paymentForm['payment_mode'],
                        'reference' => $this->paymentForm['reference'],
                        'receipt_number' => $this->paymentForm['receipt_number'],
                        'recorded_by' => auth()->id(),
                    ]);
                }

                $this->refreshInvoicePaymentSummary($targetInvoice);
            }
        });

        $this->editingPaymentId = null;
        $this->payingInvoiceId = null;
        $this->resetPaymentForm();
    }

    public function editPayment(int $paymentId): void
    {
        $payment = FeePayment::with('invoice')->findOrFail($paymentId);
        $this->editingPaymentId = $payment->id;
        $this->payingInvoiceId = $payment->fee_invoice_id;
        $this->paymentForm = [
            'fee_invoice_id' => (string) $payment->fee_invoice_id,
            'amount' => $payment->amount,
            'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
            'payment_mode' => $payment->payment_mode,
            'reference' => $payment->reference,
            'receipt_number' => $payment->receipt_number,
            'scholarship_amount' => optional($payment->invoice)->scholarship_amount,
        ];
    }

    public function cancelPaymentEdit(): void
    {
        $this->editingPaymentId = null;
        $this->payingInvoiceId = null;
        $this->resetPaymentForm();
    }

    protected function resetInvoiceForm(?int $studentId = null): void
    {
        $this->editingInvoiceId = null;
        $this->invoiceForm = [
            'student_id' => $studentId ? (string) $studentId : '',
            'billing_month' => now()->format('Y-m-01'),
            'amount_due' => '',
            'due_date' => now()->endOfMonth()->format('Y-m-d'),
            'notes' => '',
        ];
    }

    protected function resetPaymentForm(): void
    {
        $this->paymentForm = [
            'fee_invoice_id' => '',
            'amount' => '',
            'payment_date' => now()->format('Y-m-d'),
            'payment_mode' => '',
            'reference' => '',
            'receipt_number' => '',
            'scholarship_amount' => '',
        ];
    }

    protected function refreshInvoicePaymentSummary(FeeInvoice $invoice): void
    {
        $paidAmount = (float) $invoice->payments()->sum('amount');
        $lastPayment = $invoice->payments()->latest('payment_date')->latest('id')->first();

        $invoice->amount_paid = $paidAmount;
        $invoice->payment_mode_last = $lastPayment?->payment_mode;
        $invoice->status = $paidAmount >= $invoice->amount_due
            ? 'paid'
            : ($paidAmount > 0 ? 'partial' : 'pending');
        $invoice->save();
    }
}
