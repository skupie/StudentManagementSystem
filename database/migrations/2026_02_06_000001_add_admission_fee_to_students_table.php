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
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'admission_fee')) {
                $table->decimal('admission_fee', 10, 2)->default(0)->after('monthly_fee');
            }
        });

        if (! Schema::hasTable('manual_incomes')) {
            return;
        }

        Schema::table('manual_incomes', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_incomes', 'receipt_number')) {
                $table->string('receipt_number', 100)->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'admission_fee')) {
                $table->dropColumn('admission_fee');
            }
        });

        if (! Schema::hasTable('manual_incomes')) {
            return;
        }

        Schema::table('manual_incomes', function (Blueprint $table) {
            if (Schema::hasColumn('manual_incomes', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
        });
    }
};
