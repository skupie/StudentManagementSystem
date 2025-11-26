<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('role');
            $table->decimal('payment', 10, 2)->nullable()->after('subject');
            $table->string('contact_number', 50)->nullable()->after('payment');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subject', 'payment', 'contact_number']);
        });
    }
};
