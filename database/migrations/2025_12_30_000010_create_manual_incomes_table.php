<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('manual_incomes')) {
            return;
        }

        Schema::create('manual_incomes', function (Blueprint $table) {
            $table->id();
            $table->date('income_date');
            $table->string('category')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('receipt_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_incomes');
    }
};
