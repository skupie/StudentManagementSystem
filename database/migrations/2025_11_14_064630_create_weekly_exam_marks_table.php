<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weekly_exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('class_level', ['hsc_1', 'hsc_2']);
            $table->enum('section', ['science', 'humanities', 'business_studies']);
            $table->string('subject')->default('General');
            $table->date('exam_date');
            $table->decimal('marks_obtained', 8, 2);
            $table->integer('max_marks')->default(100);
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['class_level', 'section', 'exam_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_exam_marks');
    }
};
