<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routines')) {
            return;
        }

        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->enum('class_level', ['hsc_1', 'hsc_2']);
            $table->enum('section', ['science', 'humanities', 'business_studies']);
            $table->date('routine_date')->default(now());
            $table->string('time_slot');
            $table->string('subject');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
