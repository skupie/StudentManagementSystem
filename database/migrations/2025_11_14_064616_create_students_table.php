<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone_number')->unique();
            $table->enum('class_level', ['hsc_1', 'hsc_2']);
            $table->string('academic_year');
            $table->enum('section', ['science', 'humanities', 'business_studies']);
            $table->decimal('monthly_fee', 10, 2);
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->boolean('full_payment_override')->default(false);
            $table->date('enrollment_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_passed')->default(false);
            $table->string('passed_year', 9)->nullable();
            $table->timestamp('inactive_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
