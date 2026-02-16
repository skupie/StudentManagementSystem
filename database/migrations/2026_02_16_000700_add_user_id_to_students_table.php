<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || Schema::hasColumn('students', 'user_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'user_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
