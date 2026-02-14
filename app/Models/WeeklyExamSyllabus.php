<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyExamSyllabus extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_start_date',
        'title',
        'class_level',
        'section',
        'subject',
        'syllabus_details',
        'created_by',
    ];

    protected $casts = [
        'week_start_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
