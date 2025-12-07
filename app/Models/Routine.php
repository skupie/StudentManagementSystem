<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Routine extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_level',
        'section',
        'routine_date',
        'time_slot',
        'subject',
        'teacher_id',
        'created_by',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
