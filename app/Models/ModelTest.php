<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'type',
        'year',
    ];

    public function results()
    {
        return $this->hasMany(ModelTestResult::class);
    }
}
