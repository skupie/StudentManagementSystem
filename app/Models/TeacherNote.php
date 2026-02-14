<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'class_level',
        'section',
        'target_classes',
        'target_sections',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'target_classes' => 'array',
        'target_sections' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function classTargets(): array
    {
        $targets = array_values(array_filter((array) ($this->target_classes ?? [])));
        if (! empty($targets)) {
            return $targets;
        }

        return $this->class_level ? [$this->class_level] : [];
    }

    public function sectionTargets(): array
    {
        $targets = array_values(array_filter((array) ($this->target_sections ?? [])));
        if (! empty($targets)) {
            return $targets;
        }

        return $this->section ? [$this->section] : [];
    }
}
