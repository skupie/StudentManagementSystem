<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDueAlertState extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'dismissed_until',
        'force_show_due_alert',
        'last_manual_trigger_at',
    ];

    protected $casts = [
        'dismissed_until' => 'datetime',
        'force_show_due_alert' => 'boolean',
        'last_manual_trigger_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

