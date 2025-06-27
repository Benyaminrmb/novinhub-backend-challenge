<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'time_slot_id',
    ];

    /**
     * Relationship with the user (client)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with the time slot
     */
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * Scope to get reservations for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get future reservations
     */
    public function scopeFuture($query)
    {
        return $query->whereHas('timeSlot', function ($q) {
            $q->where('start_time', '>', now());
        });
    }
}
