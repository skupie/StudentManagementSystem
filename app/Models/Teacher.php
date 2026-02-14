<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'subjects',
        'payment',
        'contact_number',
        'is_active',
        'note',
        'created_by',
        'available_days',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'payment' => 'decimal:2',
        'available_days' => 'array',
        'subjects' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function loginUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function weeklyExamAssignments()
    {
        return $this->hasMany(WeeklyExamAssignment::class);
    }
}
