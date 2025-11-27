<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->decimal('previous_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('previous_scholarship_amount', 10, 2)->nullable()->after('previous_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn(['previous_amount', 'previous_scholarship_amount']);
        });
    }
};
