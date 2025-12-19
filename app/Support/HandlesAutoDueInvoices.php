<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\FeeInvoice;
use Carbon\Carbon;

trait HandlesAutoDueInvoices
{
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

    protected function syncDueInvoiceForPartial(FeeInvoice $invoice): void
    {
        $invoice->refresh();

        $monthStart = Carbon::parse($invoice->billing_month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $isAutoDue = str_starts_with((string) ($invoice->notes ?? ''), '[Auto Due]');

        $baseInvoice = $isAutoDue
            ? FeeInvoice::lockForUpdate()
                ->where('student_id', $invoice->student_id)
                ->whereDate('billing_month', $monthStart)
                ->orderBy('billing_month')
                ->first()
            : $invoice;

        $autoDueInvoice = FeeInvoice::lockForUpdate()
            ->where('student_id', $invoice->student_id)
            ->whereDate('billing_month', $monthEnd)
            ->where('id', '!=', $baseInvoice?->id ?? 0)
            ->first();

        $originalNet = round((float) ($baseInvoice?->amount_due ?? 0) + (float) ($autoDueInvoice?->amount_due ?? 0), 2);
        $totalPaid = round((float) ($baseInvoice?->amount_paid ?? 0) + (float) ($autoDueInvoice?->amount_paid ?? 0), 2);

        if ($totalPaid < 0.01) {
            if ($autoDueInvoice && ! $autoDueInvoice->payments()->exists()) {
                $autoDueInvoice->delete();
            }

            return;
        }

        $outstanding = round(max(0, $originalNet - $totalPaid), 2);

        if ($outstanding < 0.01) {
            if ($autoDueInvoice) {
                if ($autoDueInvoice->payments()->exists()) {
                    $this->refreshInvoicePaymentSummary($autoDueInvoice);

                    $autoDueInvoice->amount_due = $autoDueInvoice->amount_paid;
                    $autoDueInvoice->status = 'paid';
                    $autoDueInvoice->manual_override = true;
                    $autoDueInvoice->notes = $this->autoDueNote($monthStart);
                    $autoDueInvoice->billing_month = $monthEnd;
                    $autoDueInvoice->due_date = $autoDueInvoice->due_date ?? $monthEnd;
                    $autoDueInvoice->save();
                } else {
                    $autoDueInvoice->delete();
                }
            }

            if ($baseInvoice) {
                $this->refreshInvoicePaymentSummary($baseInvoice);
                $baseInvoice->amount_due = round((float) $baseInvoice->amount_paid, 2);
                $baseInvoice->status = 'paid';
                $baseInvoice->manual_override = true;
                $baseInvoice->save();
            }

            return;
        }

        if ($baseInvoice) {
            $this->refreshInvoicePaymentSummary($baseInvoice);

            $baseInvoice->amount_due = round((float) $baseInvoice->amount_paid, 2);
            $baseInvoice->status = 'paid';
            $baseInvoice->manual_override = true;
            $baseInvoice->save();
        }

        $dueDate = $baseInvoice?->due_date ?? $invoice->due_date ?? $monthEnd;
        $note = $this->autoDueNote($monthStart);

        if ($autoDueInvoice) {
            $this->refreshInvoicePaymentSummary($autoDueInvoice);

            $autoDueInvoice->billing_month = $monthEnd;
            $autoDueInvoice->due_date = $dueDate;
            $autoDueInvoice->amount_due = round($outstanding + (float) $autoDueInvoice->amount_paid, 2);
            $autoDueInvoice->scholarship_amount = 0;
            $autoDueInvoice->was_active = $baseInvoice?->was_active ?? $invoice->was_active;
            $autoDueInvoice->manual_override = true;
            $autoDueInvoice->notes = $note;
            $autoDueInvoice->status = $autoDueInvoice->amount_paid >= $autoDueInvoice->amount_due
                ? 'paid'
                : ($autoDueInvoice->amount_paid > 0 ? 'partial' : 'pending');
            $autoDueInvoice->save();
        } else {
            $dueInvoice = FeeInvoice::create([
                'student_id' => $invoice->student_id,
                'billing_month' => $monthEnd,
                'due_date' => $dueDate,
                'amount_due' => $outstanding,
                'scholarship_amount' => 0,
                'amount_paid' => 0,
                'status' => 'pending',
                'was_active' => $baseInvoice?->was_active ?? $invoice->was_active,
                'manual_override' => true,
                'notes' => $note,
            ]);

            AuditLog::record(
                'invoice.create',
                'Auto due invoice created for ' . optional($invoice->student)->name . ' (' . $monthStart->format('M Y') . ')',
                $dueInvoice,
                [
                    'student_id' => $dueInvoice->student_id,
                    'billing_month' => $dueInvoice->billing_month->toDateString(),
                    'amount_due' => $dueInvoice->amount_due,
                ]
            );
        }
    }

    protected function autoDueNote(Carbon $month): string
    {
        return '[Auto Due] Remaining from ' . $month->format('M Y');
    }
}
