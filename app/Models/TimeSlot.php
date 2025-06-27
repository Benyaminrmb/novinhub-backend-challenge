<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeSlot extends Model
{
    /** @use HasFactory<\Database\Factories\TimeSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relationship with the consultant (User)
     */
    public function consultant()
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    /**
     * Relationship with reservations
     */
    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }

    /**
     * Check if this time slot is available (not reserved)
     */
    public function isAvailable(): bool
    {
        return !$this->reservation()->exists();
    }

    /**
     * Check if this time slot is in the future
     */
    public function isFuture(): bool
    {
        return $this->start_time->isFuture();
    }

    /**
     * Scope to get available time slots
     */
    public function scopeAvailable($query)
    {
        return $query->whereDoesntHave('reservation');
    }

    /**
     * Scope to get future time slots
     */
    public function scopeFuture($query)
    {
        return $query->where('start_time', '>', now());
    }

    /**
     * Scope to get time slots for a specific consultant
     */
    public function scopeForConsultant($query, $consultantId)
    {
        return $query->where('consultant_id', $consultantId);
    }

    /**
     * Check if this time slot overlaps with another time slot
     */
    public function overlapsWithTimeSlot($startTime, $endTime, $excludeId = null): bool
    {
        $query = static::where('consultant_id', $this->consultant_id)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('start_time', '<=', $startTime)
                         ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Static method to check for overlapping time slots
     */
    public static function hasOverlappingSlots($consultantId, $startTime, $endTime, $excludeId = null): bool
    {
        $query = static::where('consultant_id', $consultantId)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    // New slot starts within existing slot
                    $q2->where('start_time', '<=', $startTime)
                       ->where('end_time', '>', $startTime);
                })->orWhere(function ($q2) use ($startTime, $endTime) {
                    // New slot ends within existing slot
                    $q2->where('start_time', '<', $endTime)
                       ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q2) use ($startTime, $endTime) {
                    // New slot completely contains existing slot
                    $q2->where('start_time', '>=', $startTime)
                       ->where('end_time', '<=', $endTime);
                })->orWhere(function ($q2) use ($startTime, $endTime) {
                    // Existing slot completely contains new slot
                    $q2->where('start_time', '<=', $startTime)
                       ->where('end_time', '>=', $endTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
