<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_notices')) {
            Schema::create('student_notices', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body');
                $table->date('notice_date');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'notice_date']);
            });
        }

        if (! Schema::hasTable('student_notice_acknowledgements')) {
            Schema::create('student_notice_acknowledgements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_notice_id')->constrained('student_notices')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->enum('action', ['acknowledged', 'closed'])->default('acknowledged');
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();

                $table->unique(['student_notice_id', 'student_id'], 'student_notice_student_unique');
                $table->index(['student_id', 'acknowledged_at'], 'student_notice_student_ack_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notice_acknowledgements');
        Schema::dropIfExists('student_notices');
    }
};

