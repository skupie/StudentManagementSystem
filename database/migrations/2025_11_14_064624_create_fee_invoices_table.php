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
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('billing_month');
            $table->date('due_date')->nullable();
            $table->decimal('amount_due', 10, 2);
            $table->decimal('scholarship_amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');
            $table->string('payment_mode_last')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('was_active')->default(true);
            $table->boolean('manual_override')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'billing_month']);
            $table->index(['billing_month', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_invoices');
    }
};
