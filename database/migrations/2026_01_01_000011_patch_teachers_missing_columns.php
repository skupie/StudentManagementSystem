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
            if (! Schema::hasColumn('teachers', 'name')) {
                $table->string('name')->after('id');
            }
            if (! Schema::hasColumn('teachers', 'subject')) {
                $table->string('subject')->nullable()->after('name');
            }
            if (! Schema::hasColumn('teachers', 'subjects')) {
                $table->json('subjects')->nullable()->after('subject');
            }
            if (! Schema::hasColumn('teachers', 'payment')) {
                $table->decimal('payment', 10, 2)->nullable()->after('subject');
            }
            if (! Schema::hasColumn('teachers', 'contact_number')) {
                $table->string('contact_number', 50)->nullable()->after('payment');
            }
            if (! Schema::hasColumn('teachers', 'available_days')) {
                $table->json('available_days')->nullable()->after('contact_number');
            }
            if (! Schema::hasColumn('teachers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('available_days');
            }
            if (! Schema::hasColumn('teachers', 'note')) {
                $table->text('note')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('teachers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('note');
            }
            if (! Schema::hasColumn('teachers', 'created_at') && ! Schema::hasColumn('teachers', 'updated_at')) {
                $table->timestamps();
            } elseif (! Schema::hasColumn('teachers', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            } elseif (! Schema::hasColumn('teachers', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teachers')) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            foreach (['note', 'is_active', 'available_days', 'contact_number', 'payment', 'subjects', 'subject', 'name'] as $column) {
                if (Schema::hasColumn('teachers', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('teachers', 'created_at') && Schema::hasColumn('teachers', 'updated_at')) {
                $table->dropTimestamps();
            } elseif (Schema::hasColumn('teachers', 'created_at')) {
                $table->dropColumn('created_at');
            } elseif (Schema::hasColumn('teachers', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
