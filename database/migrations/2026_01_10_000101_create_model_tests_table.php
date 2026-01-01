<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_tests')) {
            return;
        }

        Schema::create('model_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('type', 20); // full, mcq, cq
            $table->unsignedInteger('year')->default((int) date('Y'));
            $table->timestamps();

            $table->index(['year', 'type']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_tests');
    }
};
