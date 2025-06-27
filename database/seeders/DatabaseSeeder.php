<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        User::factory()->create([
            'name' => 'Test Consultant',
            'email' => 'consultant@example.com',
            'password' => Hash::make('password'),
            'role' => 'consultant',
        ]);

        User::factory()->create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        // Create additional random users
        User::factory()->consultant()->count(5)->create();
        User::factory()->client()->count(10)->create();

        // Seed time slots and reservations
        $this->call([
            TimeSlotSeeder::class,
        ]);
    }
}
