<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sign_in_at',
        'sign_out_at',
    ];

    protected $casts = [
        'sign_in_at' => 'datetime',
        'sign_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
