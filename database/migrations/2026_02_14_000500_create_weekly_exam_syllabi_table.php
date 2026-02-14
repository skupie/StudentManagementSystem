<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('weekly_exam_syllabi')) {
            return;
        }

        Schema::create('weekly_exam_syllabi', function (Blueprint $table) {
            $table->id();
            $table->date('week_start_date');
            $table->string('title');
            $table->string('class_level', 50);
            $table->string('section', 50);
            $table->string('subject', 100);
            $table->text('syllabus_details');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['week_start_date', 'class_level', 'section'], 'wes_week_class_section_idx');
            $table->index(['subject', 'week_start_date'], 'wes_subject_week_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_exam_syllabi');
    }
};
