<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class TimeSlotApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $consultant;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->consultant = User::factory()->consultant()->create();
        $this->client = User::factory()->client()->create();
    }

    public function test_anyone_can_view_available_time_slots()
    {
        $availableTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->tomorrow()
            ->create();

        $response = $this->getJson('/api/timeslots/available');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'start_time',
                        'end_time',
                        'consultant' => ['id', 'name']
                    ]
                ]
            ])
            ->assertJsonFragment([
                'id' => $availableTimeSlot->id,
            ]);
    }

    public function test_consultant_can_create_time_slot()
    {
        $token = $this->consultant->createToken('test-token')->plainTextToken;
        
        $startTime = Carbon::tomorrow()->setTime(9, 0)->toISOString();
        $endTime = Carbon::tomorrow()->setTime(10, 0)->toISOString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/timeslots', [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'consultant_id',
                    'start_time',
                    'end_time',
                    'consultant' => ['id', 'name']
                ]
            ])
            ->assertJson([
                'message' => 'Time slot created successfully',
                'data' => [
                    'consultant_id' => $this->consultant->id,
                ]
            ]);

        $this->assertDatabaseHas('time_slots', [
            'consultant_id' => $this->consultant->id,
            'start_time' => Carbon::parse($startTime),
            'end_time' => Carbon::parse($endTime),
        ]);
    }

    public function test_client_cannot_create_time_slot()
    {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/timeslots', [
                'start_time' => Carbon::tomorrow()->setTime(9, 0)->toISOString(),
                'end_time' => Carbon::tomorrow()->setTime(10, 0)->toISOString(),
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only consultants can create time slots']);
    }

    public function test_cannot_create_overlapping_time_slots()
    {
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        // Create first time slot
        $existingStartTime = Carbon::tomorrow()->setTime(9, 0);
        $existingEndTime = Carbon::tomorrow()->setTime(10, 0);
        
        TimeSlot::factory()->forConsultant($this->consultant)->create([
            'start_time' => $existingStartTime,
            'end_time' => $existingEndTime,
        ]);

        // Try to create overlapping slot
        $overlappingStartTime = Carbon::tomorrow()->setTime(9, 30)->toISOString();
        $overlappingEndTime = Carbon::tomorrow()->setTime(11, 0)->toISOString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/timeslots', [
                'start_time' => $overlappingStartTime,
                'end_time' => $overlappingEndTime,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Time slot overlaps with existing time slots',
                'errors' => [
                    'time_conflict' => ['This time slot conflicts with your existing time slots']
                ]
            ]);
    }

    public function test_time_slot_validation_rules()
    {
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        // Test missing required fields
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/timeslots', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time', 'end_time']);

        // Test end time before start time
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/timeslots', [
                'start_time' => Carbon::tomorrow()->setTime(10, 0)->toISOString(),
                'end_time' => Carbon::tomorrow()->setTime(9, 0)->toISOString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);

        // Test past start time
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/timeslots', [
                'start_time' => Carbon::yesterday()->setTime(9, 0)->toISOString(),
                'end_time' => Carbon::yesterday()->setTime(10, 0)->toISOString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_consultant_can_view_their_time_slots()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/timeslots');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'consultant_id',
                        'start_time',
                        'end_time'
                    ]
                ]
            ])
            ->assertJsonFragment([
                'id' => $timeSlot->id,
                'consultant_id' => $this->consultant->id,
            ]);
    }

    public function test_client_cannot_view_consultant_time_slots()
    {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/timeslots');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only consultants can view their time slots']);
    }

    public function test_consultant_can_update_their_time_slot()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        // Debug output
        dump("Consultant ID: " . $this->consultant->id);
        dump("Consultant Role: " . $this->consultant->role);
        dump("TimeSlot Consultant ID: " . $timeSlot->consultant_id);

        $newStartTime = Carbon::tomorrow()->setTime(14, 0)->toISOString();
        $newEndTime = Carbon::tomorrow()->setTime(15, 0)->toISOString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/timeslots/{$timeSlot->id}", [
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
            ]);

        // Debug the response
        dump("Response Status: " . $response->getStatusCode());
        dump("Response Body: " . $response->getContent());

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Time slot updated successfully',
                'data' => [
                    'id' => $timeSlot->id,
                ]
            ]);

        $this->assertDatabaseHas('time_slots', [
            'id' => $timeSlot->id,
            'start_time' => Carbon::parse($newStartTime),
            'end_time' => Carbon::parse($newEndTime),
        ]);
    }

    public function test_consultant_cannot_update_reserved_time_slot()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
        
        // Reserve the time slot
        $timeSlot->reservation()->create([
            'user_id' => $this->client->id,
            'time_slot_id' => $timeSlot->id,
        ]);

        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/timeslots/{$timeSlot->id}", [
                'start_time' => Carbon::tomorrow()->setTime(14, 0)->toISOString(),
                'end_time' => Carbon::tomorrow()->setTime(15, 0)->toISOString(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot update a reserved time slot']);
    }

    public function test_consultant_cannot_update_other_consultants_time_slot()
    {
        $otherConsultant = User::factory()->consultant()->create();
        $timeSlot = TimeSlot::factory()->forConsultant($otherConsultant)->create();
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/timeslots/{$timeSlot->id}", [
                'start_time' => Carbon::tomorrow()->setTime(14, 0)->toISOString(),
                'end_time' => Carbon::tomorrow()->setTime(15, 0)->toISOString(),
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'You can only update your own time slots']);
    }

    public function test_consultant_can_delete_their_time_slot()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/timeslots/{$timeSlot->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Time slot deleted successfully']);

        $this->assertDatabaseMissing('time_slots', [
            'id' => $timeSlot->id,
        ]);
    }

    public function test_consultant_cannot_delete_reserved_time_slot()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
        
        // Reserve the time slot
        $timeSlot->reservation()->create([
            'user_id' => $this->client->id,
            'time_slot_id' => $timeSlot->id,
        ]);

        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/timeslots/{$timeSlot->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete a reserved time slot']);
    }

    public function test_unauthenticated_user_cannot_access_protected_time_slot_endpoints()
    {
        $timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();

        $response = $this->postJson('/api/timeslots', [
            'start_time' => Carbon::tomorrow()->setTime(9, 0)->toISOString(),
            'end_time' => Carbon::tomorrow()->setTime(10, 0)->toISOString(),
        ]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/timeslots');
        $response->assertStatus(401);

        $response = $this->putJson("/api/timeslots/{$timeSlot->id}", [
            'start_time' => Carbon::tomorrow()->setTime(14, 0)->toISOString(),
            'end_time' => Carbon::tomorrow()->setTime(15, 0)->toISOString(),
        ]);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/timeslots/{$timeSlot->id}");
        $response->assertStatus(401);
    }
}
