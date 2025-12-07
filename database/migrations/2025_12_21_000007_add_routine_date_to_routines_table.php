<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('routines')) {
            return;
        }

        Schema::table('routines', function (Blueprint $table) {
            if (! Schema::hasColumn('routines', 'routine_date')) {
                $table->date('routine_date')->default(now())->after('section');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('routines')) {
            return;
        }

        Schema::table('routines', function (Blueprint $table) {
            if (Schema::hasColumn('routines', 'routine_date')) {
                $table->dropColumn('routine_date');
            }
        });
    }
};
