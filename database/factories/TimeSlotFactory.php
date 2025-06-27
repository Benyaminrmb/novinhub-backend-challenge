<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create a random future start time (1-30 days from now)
        $startTime = Carbon::now()
            ->addDays(fake()->numberBetween(1, 30))
            ->setTime(fake()->numberBetween(9, 17), fake()->randomElement([0, 30]), 0);
        
        // End time is 1-2 hours after start time
        $endTime = (clone $startTime)->addHours(fake()->numberBetween(1, 2));

        return [
            'consultant_id' => User::factory()->consultant(),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    /**
     * Create time slot for a specific consultant
     */
    public function forConsultant(User $consultant): static
    {
        return $this->state(fn (array $attributes) => [
            'consultant_id' => $consultant->id,
        ]);
    }

    /**
     * Create time slot for today
     */
    public function today(): static
    {
        $startTime = Carbon::today()
            ->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]), 0);
        $endTime = (clone $startTime)->addHours(fake()->numberBetween(1, 2));

        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Create time slot for tomorrow
     */
    public function tomorrow(): static
    {
        $startTime = Carbon::tomorrow()
            ->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]), 0);
        $endTime = (clone $startTime)->addHours(fake()->numberBetween(1, 2));

        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Create time slot in the past (for testing)
     */
    public function past(): static
    {
        $startTime = Carbon::now()
            ->subDays(fake()->numberBetween(1, 10))
            ->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]), 0);
        $endTime = (clone $startTime)->addHours(fake()->numberBetween(1, 2));

        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
