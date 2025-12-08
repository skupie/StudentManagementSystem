<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teachers')) {
            // Cannot create payments table without teachers
            return;
        }

        if (Schema::hasTable('teacher_payments')) {
            return;
        }

        Schema::create('teacher_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->date('payout_month'); // stored as first day of month
            $table->unsignedInteger('class_count')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('logged_at')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'payout_month']);
            $table->index('payout_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_payments');
    }
};
