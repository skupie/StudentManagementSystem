<?php

namespace App\Livewire\Fees;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Support\AcademyOptions;
use Livewire\Component;

class DueList extends Component
{
    public bool $embedded = false;

    public string $classFilter = 'all';
    public string $sectionFilter = 'all';
    public string $yearFilter = '';
    public string $nameFilter = '';

    public string $paymentMode = 'Cash';
    public string $paymentDate;
    public string $paymentReference = '';

    public array $settlement = [];

    public function mount(bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->paymentDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $students = Student::query()
            ->with(['feeInvoices' => fn ($q) => $q->orderBy('billing_month')])
            ->when($this->classFilter !== 'all', fn ($q) => $q->where('class_level', $this->classFilter))
            ->when($this->sectionFilter !== 'all', fn ($q) => $q->where('section', $this->sectionFilter))
            ->when($this->yearFilter, fn ($q) => $q->where('academic_year', 'like', '%' . $this->yearFilter . '%'))
            ->when($this->nameFilter, fn ($q) => $q->where('name', 'like', '%' . $this->nameFilter . '%'))
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $summary = $student->dueSummary();
                $student->outstanding = $summary['amount'];
                $student->due_months = implode(', ', $summary['months']);
                return $student;
            })
            ->filter(fn (Student $student) => $student->outstanding > 0)
            ->values();

        $totalDue = $students->sum('outstanding');

        return view('livewire.fees.due-list', [
            'students' => $students,
            'totalDue' => $totalDue,
            'classOptions' => ['all' => 'All Classes'] + AcademyOptions::classes(),
            'sectionOptions' => ['all' => 'All Sections'] + AcademyOptions::sections(),
        ]);
    }

    public function receivePayment(int $studentId): void
    {
        $amount = (float) ($this->settlement[$studentId] ?? 0);
        if ($amount <= 0) {
            return;
        }

        $student = Student::findOrFail($studentId);
        $student->ensureInvoicesThroughMonth(now());

        $invoices = FeeInvoice::query()
            ->where('student_id', $studentId)
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->orderBy('billing_month')
            ->get();

        $remaining = $amount;

        foreach ($invoices as $invoice) {
            $student->adjustInvoiceForAttendance($invoice);

            if ($remaining <= 0) {
                break;
            }

            $invoiceOutstanding = $invoice->amount_due - $invoice->amount_paid;
            $applyAmount = min($invoiceOutstanding, $remaining);

            FeePayment::create([
                'fee_invoice_id' => $invoice->id,
                'student_id' => $studentId,
                'amount' => $applyAmount,
                'payment_date' => $this->paymentDate,
                'payment_mode' => $this->paymentMode,
                'reference' => $this->paymentReference,
                'recorded_by' => auth()->id(),
            ]);

            $invoice->amount_paid += $applyAmount;
            $invoice->status = $invoice->amount_paid >= $invoice->amount_due ? 'paid' : 'partial';
            $invoice->payment_mode_last = $this->paymentMode;
            $invoice->save();

            $remaining -= $applyAmount;
        }

        $this->settlement[$studentId] = '';
    }

    public function exportPdf()
    {
        return redirect()->route('reports.due-list.pdf', [
            'class' => $this->classFilter,
            'section' => $this->sectionFilter,
            'year' => $this->yearFilter,
        ]);
    }

    public function exportExcel()
    {
        return redirect()->route('reports.due-list.excel', [
            'class' => $this->classFilter,
            'section' => $this->sectionFilter,
            'year' => $this->yearFilter,
        ]);
    }
}
