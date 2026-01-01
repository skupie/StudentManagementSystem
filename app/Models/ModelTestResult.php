<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_test_id',
        'model_test_student_id',
        'year',
        'subject',
        'optional_subject',
        'mcq_mark',
        'cq_mark',
        'practical_mark',
        'total_mark',
        'grade',
        'grade_point',
    ];

    protected $casts = [
        'mcq_mark' => 'float',
        'cq_mark' => 'float',
        'practical_mark' => 'float',
        'total_mark' => 'float',
        'grade_point' => 'float',
        'optional_subject' => 'boolean',
    ];

    public function test()
    {
        return $this->belongsTo(ModelTest::class, 'model_test_id');
    }

    public function student()
    {
        return $this->belongsTo(ModelTestStudent::class, 'model_test_student_id');
    }

    public static function gradeForScore(float $score): array
    {
        $breakdown = [
            80 => ['A+', 5.00],
            70 => ['A', 4.00],
            60 => ['A-', 3.50],
            50 => ['B', 3.00],
            40 => ['C', 2.00],
            33 => ['D', 1.00],
            0 => ['F', 0.00],
        ];

        foreach ($breakdown as $min => [$grade, $point]) {
            if ($score >= $min) {
                return ['grade' => $grade, 'point' => $point];
            }
        }

        return ['grade' => 'F', 'point' => 0.00];
    }
}
