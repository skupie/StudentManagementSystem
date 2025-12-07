<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'payout_month',
        'class_count',
        'amount',
        'expense_id',
        'note',
        'logged_at',
    ];

    protected $casts = [
        'payout_month' => 'date',
        'amount' => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
