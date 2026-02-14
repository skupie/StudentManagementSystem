<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('weekly_exam_assignments')) {
            return;
        }

        Schema::create('weekly_exam_assignments', function (Blueprint $table) {
            $table->id();
            $table->date('exam_date');
            $table->string('exam_name');
            $table->string('class_level', 50);
            $table->string('section', 50);
            $table->string('subject', 100);
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['teacher_id', 'exam_date'], 'wea_teacher_date_idx');
            $table->index(['class_level', 'section', 'subject', 'exam_date'], 'wea_cls_sec_subj_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_exam_assignments');
    }
};
