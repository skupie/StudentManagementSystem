<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_invoices', 'manual_override')) {
                $table->boolean('manual_override')->default(false)->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('fee_invoices', 'manual_override')) {
                $table->dropColumn('manual_override');
            }
        });
    }
};
