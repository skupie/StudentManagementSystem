<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNoticeAcknowledgement extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_notice_id',
        'student_id',
        'action',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(StudentNotice::class, 'student_notice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

