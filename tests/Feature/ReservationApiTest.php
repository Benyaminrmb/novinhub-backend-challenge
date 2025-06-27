<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TimeSlot;
use App\Models\Reservation;
use App\Events\ReservationCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Carbon\Carbon;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $consultant;
    protected $client;
    protected $timeSlot;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->consultant = User::factory()->consultant()->create();
        $this->client = User::factory()->client()->create();
        $this->timeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->tomorrow()
            ->create();
    }

    public function test_client_can_create_reservation()
    {
        Event::fake();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'time_slot_id' => $this->timeSlot->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'time_slot_id',
                    'time_slot' => [
                        'id',
                        'start_time',
                        'end_time',
                        'consultant' => ['id', 'name']
                    ]
                ]
            ])
            ->assertJson([
                'message' => 'Reservation created successfully',
                'data' => [
                    'user_id' => $this->client->id,
                    'time_slot_id' => $this->timeSlot->id,
                ]
            ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->client->id,
            'time_slot_id' => $this->timeSlot->id,
        ]);

        // Verify event was dispatched
        Event::assertDispatched(ReservationCreated::class);
    }

    public function test_consultant_cannot_create_reservation()
    {
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'time_slot_id' => $this->timeSlot->id,
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only clients can make reservations']);
    }

    public function test_cannot_reserve_past_time_slot()
    {
        $pastTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->past()
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'time_slot_id' => $pastTimeSlot->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot reserve past time slots',
                'errors' => [
                    'time_slot_id' => ['This time slot is in the past']
                ]
            ]);
    }

    public function test_cannot_reserve_already_reserved_time_slot()
    {
        // Create initial reservation
        Reservation::factory()
            ->forTimeSlot($this->timeSlot)
            ->forUser(User::factory()->client()->create())
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'time_slot_id' => $this->timeSlot->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Time slot is already reserved',
                'errors' => [
                    'time_slot_id' => ['This time slot is no longer available']
                ]
            ]);
    }

    public function test_cannot_reserve_same_time_slot_twice()
    {
        // Create initial reservation by same user
        Reservation::factory()
            ->forTimeSlot($this->timeSlot)
            ->forUser($this->client)
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'time_slot_id' => $this->timeSlot->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'You have already reserved this time slot',
                'errors' => [
                    'time_slot_id' => ['You already have a reservation for this time slot']
                ]
            ]);
    }

    public function test_reservation_requires_valid_time_slot_id()
    {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'time_slot_id' => 999999, // Non-existent ID
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time_slot_id']);
    }

    public function test_client_can_view_their_reservations()
    {
        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'time_slot_id',
                        'time_slot' => [
                            'consultant' => ['id', 'name']
                        ]
                    ]
                ]
            ])
            ->assertJsonFragment([
                'id' => $reservation->id,
                'user_id' => $this->client->id,
            ]);
    }

    public function test_consultant_cannot_view_client_reservations()
    {
        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reservations');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only clients can view reservations']);
    }

    public function test_client_can_view_future_reservations()
    {
        $futureTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->tomorrow()
            ->create();

        $pastTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->past()
            ->create();

        $futureReservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($futureTimeSlot)
            ->create();

        $pastReservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($pastTimeSlot)
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/reservations/future');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $futureReservation->id]);
            
        // Check that only the future reservation is returned
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals($futureReservation->id, $responseData[0]['id']);
    }

    public function test_client_can_cancel_their_reservation()
    {
        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reservation cancelled successfully']);

        $this->assertDatabaseMissing('reservations', [
            'id' => $reservation->id,
        ]);
    }

    public function test_client_cannot_cancel_other_users_reservation()
    {
        $otherClient = User::factory()->client()->create();
        $reservation = Reservation::factory()
            ->forUser($otherClient)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'You can only cancel your own reservations']);
    }

    public function test_cannot_cancel_past_reservation()
    {
        $pastTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->past()
            ->create();

        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($pastTimeSlot)
            ->create();

        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/reservations/{$reservation->id}");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot cancel past reservations']);
    }

    public function test_consultant_can_view_their_reservations()
    {
        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $token = $this->consultant->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/consultant/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'time_slot_id',
                        'time_slot' => ['id', 'start_time', 'end_time'],
                        'user' => ['id', 'name']
                    ]
                ]
            ])
            ->assertJsonFragment([
                'id' => $reservation->id,
            ]);
    }

    public function test_client_cannot_view_consultant_reservations()
    {
        $token = $this->client->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/consultant/reservations');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only consultants can view their reservations']);
    }

    public function test_unauthenticated_user_cannot_access_reservation_endpoints()
    {
        $response = $this->postJson('/api/reservations', [
            'time_slot_id' => $this->timeSlot->id,
        ]);
        $response->assertStatus(401);

        $response = $this->getJson('/api/reservations');
        $response->assertStatus(401);

        $response = $this->getJson('/api/reservations/future');
        $response->assertStatus(401);

        $response = $this->getJson('/api/consultant/reservations');
        $response->assertStatus(401);
    }
}
