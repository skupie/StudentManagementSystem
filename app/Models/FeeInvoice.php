<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'billing_month',
        'due_date',
        'amount_due',
        'scholarship_amount',
        'amount_paid',
        'status',
        'was_active',
        'payment_mode_last',
        'notes',
        'manual_override',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
        'scholarship_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'was_active' => 'boolean',
        'manual_override' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->amount_due - (float) $this->amount_paid);
    }

    public function getGrossAmountAttribute(): float
    {
        return (float) $this->amount_due + (float) $this->scholarship_amount;
    }

    public function scopeOutstanding($query)
    {
        return $query->where('status', '!=', 'paid');
    }
}
