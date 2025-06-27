<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Jobs\SendReservationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendReservationConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationCreated $event): void
    {
        // Dispatch job to queue for sending email
        SendReservationEmail::dispatch($event->reservation);
    }
}
