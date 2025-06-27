<?php

namespace App\Policies;

use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TimeSlotPolicy
{
    /**
     * Determine whether the user can view any time slots.
     */
    public function viewAny(User $user): bool
    {
        return true; // Anyone can view available time slots
    }

    /**
     * Determine whether the user can view the time slot.
     */
    public function view(User $user, TimeSlot $timeSlot): bool
    {
        return true; // Anyone can view individual time slots
    }

    /**
     * Determine whether the user can create time slots.
     */
    public function create(User $user): bool
    {
        return $user->isConsultant();
    }

    /**
     * Determine whether the user can update the time slot.
     */
    public function update(User $user, TimeSlot $timeSlot): bool
    {
        return $user->isConsultant() && $user->id === $timeSlot->consultant_id;
    }

    /**
     * Determine whether the user can delete the time slot.
     */
    public function delete(User $user, TimeSlot $timeSlot): bool
    {
        return $user->isConsultant() && $user->id === $timeSlot->consultant_id;
    }
} 