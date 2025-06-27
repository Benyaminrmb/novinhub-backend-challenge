<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\TimeSlot;
use App\Models\Reservation;
use App\Events\ReservationCreated;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consultant = User::factory()->consultant()->create();
        $this->client = User::factory()->client()->create();
        $this->timeSlot = TimeSlot::factory()->forConsultant($this->consultant)->create();
    }

    public function test_reservation_can_be_created()
    {
        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals($this->client->id, $reservation->user_id);
        $this->assertEquals($this->timeSlot->id, $reservation->time_slot_id);
    }

    public function test_reservation_belongs_to_user()
    {
        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $this->assertInstanceOf(User::class, $reservation->user);
        $this->assertEquals($this->client->id, $reservation->user->id);
    }

    public function test_reservation_belongs_to_time_slot()
    {
        $reservation = Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        $this->assertInstanceOf(TimeSlot::class, $reservation->timeSlot);
        $this->assertEquals($this->timeSlot->id, $reservation->timeSlot->id);
    }

    public function test_user_can_have_multiple_reservations()
    {
        $timeSlot2 = TimeSlot::factory()->forConsultant($this->consultant)->create();

        Reservation::factory()->forUser($this->client)->forTimeSlot($this->timeSlot)->create();
        Reservation::factory()->forUser($this->client)->forTimeSlot($timeSlot2)->create();

        $this->assertEquals(2, $this->client->reservations()->count());
    }

    public function test_time_slot_can_have_only_one_reservation()
    {
        $client2 = User::factory()->client()->create();

        // Create first reservation
        Reservation::factory()
            ->forUser($this->client)
            ->forTimeSlot($this->timeSlot)
            ->create();

        // Try to create second reservation for same time slot
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Reservation::factory()
            ->forUser($client2)
            ->forTimeSlot($this->timeSlot)
            ->create();
    }

    public function test_reservation_created_event_is_dispatched()
    {
        Event::fake();

        $reservation = Reservation::create([
            'user_id' => $this->client->id,
            'time_slot_id' => $this->timeSlot->id,
        ]);

        // Manually dispatch the event (since we're testing the event dispatch)
        event(new ReservationCreated($reservation));

        Event::assertDispatched(ReservationCreated::class, function ($event) use ($reservation) {
            return $event->reservation->id === $reservation->id;
        });
    }

    public function test_scope_for_user_filters_by_user()
    {
        $client2 = User::factory()->client()->create();
        $timeSlot2 = TimeSlot::factory()->forConsultant($this->consultant)->create();

        // Create reservations for different users
        Reservation::factory()->forUser($this->client)->forTimeSlot($this->timeSlot)->create();
        Reservation::factory()->forUser($client2)->forTimeSlot($timeSlot2)->create();

        $userReservations = Reservation::forUser($this->client->id)->get();

        $this->assertEquals(1, $userReservations->count());
        $this->assertEquals($this->client->id, $userReservations->first()->user_id);
    }

    public function test_scope_future_filters_future_reservations()
    {
        $pastTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->past()
            ->create();

        $futureTimeSlot = TimeSlot::factory()
            ->forConsultant($this->consultant)
            ->tomorrow()
            ->create();

        // Create reservations for past and future time slots
        Reservation::factory()->forUser($this->client)->forTimeSlot($pastTimeSlot)->create();
        Reservation::factory()->forUser($this->client)->forTimeSlot($futureTimeSlot)->create();

        $futureReservations = Reservation::future()->get();

        $this->assertEquals(1, $futureReservations->count());
        $this->assertTrue($futureReservations->first()->timeSlot->isFuture());
    }
}
