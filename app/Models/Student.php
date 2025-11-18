<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
        'phone_number',
        'class_level',
        'academic_year',
        'section',
        'monthly_fee',
        'enrollment_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'monthly_fee' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }

    public function feeInvoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function weeklyExamMarks(): HasMany
    {
        return $this->hasMany(WeeklyExamMark::class);
    }

    public function dueSummary(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfMonth();

        if (! $this->enrollment_date || ! $this->monthly_fee) {
            return [
                'amount' => 0,
                'months' => [],
            ];
        }

        $this->ensureInvoicesThroughMonth($asOf);

        $openInvoices = $this->feeInvoices()
            ->where('billing_month', '<=', $asOf)
            ->orderBy('billing_month')
            ->get();

        $amount = 0;
        $months = [];

        foreach ($openInvoices as $invoice) {
            $this->adjustInvoiceForAttendance($invoice);
            $due = max(0, $invoice->amount_due - $invoice->amount_paid);
            if ($due > 0.01) {
                $amount += $due;
                $months[] = Carbon::parse($invoice->billing_month)->format('M Y');
            }
        }

        return [
            'amount' => round($amount, 2),
            'months' => $months,
        ];
    }

    public function ensureInvoicesThroughMonth(?Carbon $asOf = null): void
    {
        $asOf = ($asOf ?? now())->copy()->startOfMonth();

        if (! $this->enrollment_date || ! $this->monthly_fee) {
            return;
        }

        $cursor = $this->enrollment_date->copy()->startOfMonth();
        while ($cursor->lte($asOf)) {
            $this->feeInvoices()->firstOrCreate(
                ['billing_month' => $cursor->toDateString()],
                [
                    'due_date' => $cursor->copy()->endOfMonth(),
                    'amount_due' => $this->monthly_fee,
                    'scholarship_amount' => 0,
                    'amount_paid' => 0,
                    'status' => 'pending',
                    'was_active' => $this->status === 'active',
                ]
            );

            $cursor->addMonth();
        }
    }

    public function adjustInvoiceForAttendance(FeeInvoice $invoice): void
    {
        $month = Carbon::parse($invoice->billing_month)->startOfMonth();
        $desiredAmount = $this->calculateDueAmountForMonth($month, (bool) $invoice->was_active);

        $netAmount = max(0, $desiredAmount - (float) $invoice->scholarship_amount);

        if (abs((float) $invoice->amount_due - $netAmount) > 0.01) {
            $invoice->amount_due = $netAmount;
        }

        if ($invoice->amount_paid >= $invoice->amount_due) {
            $invoice->status = 'paid';
        } elseif ($invoice->amount_paid > 0 && $invoice->amount_paid < $invoice->amount_due) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = $invoice->amount_due > 0 ? 'pending' : 'paid';
        }

        $invoice->save();
    }

    protected function calculateDueAmountForMonth(Carbon $month, bool $wasActive): float
    {
        if (! $wasActive || ! $this->monthly_fee) {
            return 0;
        }

        $attendanceCount = $this->attendanceCountForMonth($month);

        if ($attendanceCount <= 6) {
            return 0;
        }

        if ($attendanceCount <= 15) {
            return round($this->monthly_fee / 2, 2);
        }

        return round($this->monthly_fee, 2);
    }

    protected function attendanceCountForMonth(Carbon $month): int
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $this->attendances()
            ->where('status', 'present')
            ->whereBetween('attendance_date', [$start, $end])
            ->count();
    }
}
