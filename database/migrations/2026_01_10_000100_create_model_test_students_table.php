<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_test_students')) {
            return;
        }

        Schema::create('model_test_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_number', 50)->nullable();
            $table->string('section', 100);
            $table->unsignedInteger('year')->default((int) date('Y'));
            $table->timestamps();

            $table->index(['year', 'section']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_test_students');
    }
};
