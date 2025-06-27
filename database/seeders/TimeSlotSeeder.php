<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\TimeSlot;
use App\Models\Reservation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some consultants if they don't exist
        $consultants = User::where('role', 'consultant')->get();
        
        if ($consultants->count() < 3) {
            $consultants = User::factory()->consultant()->count(3)->create();
        }

        // Create some clients for reservations
        $clients = User::where('role', 'client')->get();
        
        if ($clients->count() < 5) {
            $clients = User::factory()->client()->count(5)->create();
        }

        // Create time slots for each consultant
        foreach ($consultants as $consultant) {
            // Create 10 future time slots for each consultant
            $timeSlots = TimeSlot::factory()
                ->count(10)
                ->forConsultant($consultant)
                ->create();

            // Reserve some time slots randomly
            $timeSlotsToReserve = $timeSlots->random(3);
            
            foreach ($timeSlotsToReserve as $timeSlot) {
                Reservation::factory()
                    ->forTimeSlot($timeSlot)
                    ->forUser($clients->random())
                    ->create();
            }
        }

        // Create some specific time slots for today and tomorrow for testing
        foreach ($consultants->take(2) as $consultant) {
            // Create time slots for today
            TimeSlot::factory()
                ->count(2)
                ->today()
                ->forConsultant($consultant)
                ->create();

            // Create time slots for tomorrow
            TimeSlot::factory()
                ->count(3)
                ->tomorrow()
                ->forConsultant($consultant)
                ->create();
        }

        $this->command->info('Time slots and reservations seeded successfully!');
    }
}
