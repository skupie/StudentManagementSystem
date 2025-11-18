<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@academy.test',
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Lead Instructor',
            'email' => 'instructor@academy.test',
            'role' => 'instructor',
            'is_active' => true,
        ]);
    }
}
