<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teachers')) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table) {
            if (! Schema::hasColumn('teachers', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('subject');
            }

            if (! Schema::hasColumn('teachers', 'available_days')) {
                $table->json('available_days')->nullable()->after('contact_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teachers')) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'available_days')) {
                $table->dropColumn('available_days');
            }
        });
    }
};
