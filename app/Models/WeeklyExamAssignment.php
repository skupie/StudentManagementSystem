<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyExamAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_date',
        'exam_name',
        'class_level',
        'section',
        'subject',
        'teacher_id',
        'created_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
