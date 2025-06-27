<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Determine whether the user can view any reservations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isClient() || $user->isConsultant();
    }

    /**
     * Determine whether the user can view the reservation.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        // User can view if they own the reservation or they're the consultant for the time slot
        return $reservation->user_id === $user->id || 
               ($user->isConsultant() && $reservation->timeSlot->consultant_id === $user->id);
    }

    /**
     * Determine whether the user can create reservations.
     */
    public function create(User $user): bool
    {
        return $user->isClient();
    }

    /**
     * Determine whether the user can delete the reservation.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }
} 