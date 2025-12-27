<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyExamMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_level',
        'section',
        'subject',
        'exam_date',
        'marks_obtained',
        'max_marks',
        'recorded_by',
        'remarks',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
