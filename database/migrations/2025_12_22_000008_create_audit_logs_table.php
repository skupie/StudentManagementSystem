<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 150);
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
            return;
        }

        // If table already exists (imported DB), patch missing pieces.
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action', 150)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'model_type')) {
                $table->string('model_type')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'model_id')) {
                $table->unsignedBigInteger('model_id')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'description')) {
                $table->string('description')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'meta')) {
                $table->json('meta')->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'created_at') && ! Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->timestamps();
            } elseif (! Schema::hasColumn('audit_logs', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            } elseif (! Schema::hasColumn('audit_logs', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        // Ensure `id` is primary & auto-increment (best effort, safe to run multiple times)
        $connection = Schema::getConnection();
        $hasPrimary = false;
        $isAutoIncrement = false;

        try {
            $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes('audit_logs');
            foreach ($indexes as $index) {
                if ($index->isPrimary()) {
                    $hasPrimary = true;
                    break;
                }
            }
        } catch (\Throwable $e) {
            // ignore doctrine issues; fallback below
        }

        try {
            $column = $connection->selectOne("
                SELECT EXTRA
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'audit_logs'
                  AND COLUMN_NAME = 'id'
                LIMIT 1
            ");
            if ($column && isset($column->EXTRA) && stripos($column->EXTRA, 'auto_increment') !== false) {
                $isAutoIncrement = true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Ensure correct type
        try {
            DB::statement('ALTER TABLE audit_logs MODIFY id BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        if (! $hasPrimary) {
            try {
                DB::statement('ALTER TABLE audit_logs ADD PRIMARY KEY (id)');
            } catch (\Throwable $e) {
                // ignore if it already exists
            }
        }

        if (! $isAutoIncrement) {
            try {
                DB::statement('ALTER TABLE audit_logs MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            } catch (\Throwable $e) {
                // ignore if already auto-increment
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
