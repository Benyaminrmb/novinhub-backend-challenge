<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\TimeSlot;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->client(),
            'time_slot_id' => TimeSlot::factory(),
        ];
    }

    /**
     * Create reservation for a specific user
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create reservation for a specific time slot
     */
    public function forTimeSlot(TimeSlot $timeSlot): static
    {
        return $this->state(fn (array $attributes) => [
            'time_slot_id' => $timeSlot->id,
        ]);
    }
}
