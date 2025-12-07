<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'subject')) {
                $table->string('subject')->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'payment')) {
                $table->decimal('payment', 10, 2)->nullable()->after('subject');
            }

            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('payment');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $drop = [];
            foreach (['subject', 'payment', 'contact_number'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $drop[] = $col;
                }
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
