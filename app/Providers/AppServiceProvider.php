<?php

namespace App\Providers;

use App\Events\ReservationCreated;
use App\Listeners\SendReservationConfirmation;
use App\Models\TimeSlot;
use App\Models\Reservation;
use App\Policies\TimeSlotPolicy;
use App\Policies\ReservationPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            ReservationCreated::class,
            [SendReservationConfirmation::class, 'handle']
        );

        // Register Policies
        Gate::policy(TimeSlot::class, TimeSlotPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
    }
}
