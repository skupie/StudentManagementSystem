<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (! Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action', 150)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('audit_logs', 'model_type')) {
                $table->string('model_type')->nullable()->after('action');
            }
            if (! Schema::hasColumn('audit_logs', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            }
            if (! Schema::hasColumn('audit_logs', 'description')) {
                $table->string('description')->nullable()->after('model_id');
            }
            if (! Schema::hasColumn('audit_logs', 'meta')) {
                $table->json('meta')->nullable()->after('description');
            }
            if (! Schema::hasColumn('audit_logs', 'created_at') && ! Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->timestamps();
            } elseif (! Schema::hasColumn('audit_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            } elseif (! Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Only drop the columns we add; keep table intact.
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            foreach (['description', 'meta', 'model_id', 'model_type', 'action'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('audit_logs', 'created_at') && Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->dropTimestamps();
            } elseif (Schema::hasColumn('audit_logs', 'created_at')) {
                $table->dropColumn('created_at');
            } elseif (Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
