<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_notes')) {
            return;
        }

        Schema::table('teacher_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('teacher_notes', 'target_classes')) {
                $table->json('target_classes')->nullable()->after('section');
            }

            if (! Schema::hasColumn('teacher_notes', 'target_sections')) {
                $table->json('target_sections')->nullable()->after('target_classes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_notes')) {
            return;
        }

        Schema::table('teacher_notes', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('teacher_notes', 'target_classes')) {
                $drop[] = 'target_classes';
            }
            if (Schema::hasColumn('teacher_notes', 'target_sections')) {
                $drop[] = 'target_sections';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
