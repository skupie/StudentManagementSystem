<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelTestStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_number',
        'section',
        'year',
    ];

    public function results()
    {
        return $this->hasMany(ModelTestResult::class);
    }
}
