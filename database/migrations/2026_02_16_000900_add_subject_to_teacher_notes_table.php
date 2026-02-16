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
            if (! Schema::hasColumn('teacher_notes', 'subject')) {
                $table->string('subject', 100)->nullable()->after('section');
                $table->index('subject');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_notes')) {
            return;
        }

        Schema::table('teacher_notes', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_notes', 'subject')) {
                $table->dropIndex(['subject']);
                $table->dropColumn('subject');
            }
        });
    }
};
