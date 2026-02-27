<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_test_results')) {
            return;
        }

        Schema::create('model_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_test_id')->constrained('model_tests')->cascadeOnDelete();
            $table->foreignId('model_test_student_id')->constrained('model_test_students')->cascadeOnDelete();
            $table->string('student_name')->nullable();
            $table->string('student_section')->nullable();
            $table->unsignedInteger('year')->default((int) date('Y'));
            $table->string('subject')->nullable();
            $table->unsignedTinyInteger('test_set')->default(0);
            $table->boolean('optional_subject')->default(false);
            $table->decimal('mcq_mark', 6, 2)->nullable();
            $table->decimal('cq_mark', 6, 2)->nullable();
            $table->decimal('practical_mark', 6, 2)->nullable();
            $table->decimal('mcq_max', 6, 2)->nullable();
            $table->decimal('cq_max', 6, 2)->nullable();
            $table->decimal('practical_max', 6, 2)->nullable();
            $table->decimal('total_mark', 7, 2)->nullable();
            $table->string('grade', 3)->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->timestamps();

            $table->unique(['model_test_id', 'model_test_student_id', 'year', 'subject', 'test_set'], 'mt_results_unique');
            $table->index(['year', 'grade']);
            $table->index('optional_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_test_results');
    }
};
