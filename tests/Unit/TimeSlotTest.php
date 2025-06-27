<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\TimeSlot;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimeSlotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consultant = User::factory()->consultant()->create();
    }

    public function test_time_slot_can_be_created()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();

        $this->assertInstanceOf(TimeSlot::class, $timeSlot);
        $this->assertEquals($this->consultant->id, $timeSlot->consultant_id);
    }

    public function test_time_slot_belongs_to_consultant()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();

        $this->assertInstanceOf(User::class, $timeSlot->consultant);
        $this->assertEquals($this->consultant->id, $timeSlot->consultant->id);
    }

    public function test_time_slot_is_available_when_not_reserved()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();

        $this->assertTrue($timeSlot->isAvailable());
    }

    public function test_time_slot_is_not_available_when_reserved()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
        $client = User::factory()->client()->create();
        
        $timeSlot->reservation()->create([
            'user_id' => $client->id,
            'time_slot_id' => $timeSlot->id,
        ]);

        $this->assertFalse($timeSlot->isAvailable());
    }

    public function test_time_slot_is_future_when_start_time_is_future()
    {
        $timeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->tomorrow()
            ->create();

        $this->assertTrue($timeSlot->isFuture());
    }

    public function test_time_slot_is_not_future_when_start_time_is_past()
    {
        $timeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->past()
            ->create();

        $this->assertFalse($timeSlot->isFuture());
    }

    public function test_detects_overlapping_time_slots_exact_overlap()
    {
        $startTime = Carbon::tomorrow()->setTime(9, 0);
        $endTime = Carbon::tomorrow()->setTime(10, 0);

        // Create first time slot
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Test exact overlap
        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $this->consultant->id,
            $startTime,
            $endTime
        );

        $this->assertTrue($hasOverlap);
    }

    public function test_detects_overlapping_time_slots_partial_overlap_start()
    {
        $existingStart = Carbon::tomorrow()->setTime(9, 0);
        $existingEnd = Carbon::tomorrow()->setTime(10, 0);

        // Create existing time slot
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $existingStart,
            'end_time' => $existingEnd,
        ]);

        // Test new slot that starts within existing slot
        $newStart = Carbon::tomorrow()->setTime(9, 30);
        $newEnd = Carbon::tomorrow()->setTime(11, 0);

        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $this->consultant->id,
            $newStart,
            $newEnd
        );

        $this->assertTrue($hasOverlap);
    }

    public function test_detects_overlapping_time_slots_partial_overlap_end()
    {
        $existingStart = Carbon::tomorrow()->setTime(10, 0);
        $existingEnd = Carbon::tomorrow()->setTime(11, 0);

        // Create existing time slot
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $existingStart,
            'end_time' => $existingEnd,
        ]);

        // Test new slot that ends within existing slot
        $newStart = Carbon::tomorrow()->setTime(9, 0);
        $newEnd = Carbon::tomorrow()->setTime(10, 30);

        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $this->consultant->id,
            $newStart,
            $newEnd
        );

        $this->assertTrue($hasOverlap);
    }

    public function test_detects_overlapping_time_slots_containing()
    {
        $existingStart = Carbon::tomorrow()->setTime(9, 30);
        $existingEnd = Carbon::tomorrow()->setTime(10, 30);

        // Create existing time slot
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $existingStart,
            'end_time' => $existingEnd,
        ]);

        // Test new slot that completely contains existing slot
        $newStart = Carbon::tomorrow()->setTime(9, 0);
        $newEnd = Carbon::tomorrow()->setTime(11, 0);

        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $this->consultant->id,
            $newStart,
            $newEnd
        );

        $this->assertTrue($hasOverlap);
    }

    public function test_no_overlap_when_time_slots_are_adjacent()
    {
        $existingStart = Carbon::tomorrow()->setTime(9, 0);
        $existingEnd = Carbon::tomorrow()->setTime(10, 0);

        // Create existing time slot
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $existingStart,
            'end_time' => $existingEnd,
        ]);

        // Test adjacent time slot (starts when previous ends)
        $newStart = Carbon::tomorrow()->setTime(10, 0);
        $newEnd = Carbon::tomorrow()->setTime(11, 0);

        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $this->consultant->id,
            $newStart,
            $newEnd
        );

        $this->assertFalse($hasOverlap);
    }

    public function test_no_overlap_between_different_consultants()
    {
        $anotherConsultant = User::factory()->consultant()->create();
        $startTime = Carbon::tomorrow()->setTime(9, 0);
        $endTime = Carbon::tomorrow()->setTime(10, 0);

        // Create time slot for first consultant
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Test same time for different consultant - should not overlap
        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $anotherConsultant->id,
            $startTime,
            $endTime
        );

        $this->assertFalse($hasOverlap);
    }

    public function test_exclude_self_when_checking_overlap_for_update()
    {
        $startTime = Carbon::tomorrow()->setTime(9, 0);
        $endTime = Carbon::tomorrow()->setTime(10, 0);

        // Create time slot
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Test updating the same time slot - should not detect overlap with itself
        $hasOverlap = TimeSlot::hasOverlappingSlots(
            $this->consultant->id,
            $startTime,
            $endTime,
            $timeSlot->id
        );

        $this->assertFalse($hasOverlap);
    }
}
