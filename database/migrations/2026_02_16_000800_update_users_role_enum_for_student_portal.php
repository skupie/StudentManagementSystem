<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'director', 'instructor', 'lead_instructor', 'assistant', 'student'])
                ->default('admin')
                ->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'director', 'instructor', 'assistant'])
                ->default('admin')
                ->change();
        });
    }
};
