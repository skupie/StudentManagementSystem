<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentNotice extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'notice_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(StudentNoticeAcknowledgement::class);
    }
}

