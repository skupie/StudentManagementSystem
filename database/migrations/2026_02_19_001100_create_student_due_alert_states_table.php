<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_due_alert_states')) {
            return;
        }

        Schema::create('student_due_alert_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->timestamp('dismissed_until')->nullable();
            $table->boolean('force_show_due_alert')->default(false);
            $table->timestamp('last_manual_trigger_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_due_alert_states');
    }
};

