<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableTimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * This resource is specifically for displaying available time slots for booking.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time->toISOString(),
            'duration_minutes' => $this->start_time->diffInMinutes($this->end_time),
            'formatted_time' => $this->start_time->format('Y-m-d H:i') . ' - ' . $this->end_time->format('H:i'),
            'date' => $this->start_time->format('Y-m-d'),
            'day_of_week' => $this->start_time->format('l'),
            'time_range' => $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i'),
            
            // Consultant information
            'consultant' => [
                'id' => $this->consultant->id,
                'name' => $this->consultant->name,
                'email' => $this->consultant->email,
            ],
            
            // Booking information
            'can_book' => $this->isAvailable() && $this->isFuture(),
            'status' => $this->isAvailable() 
                ? ($this->isFuture() ? 'available' : 'expired')
                : 'reserved',
                
            // Time until appointment (if in future)
            'time_until' => $this->when(
                $this->isFuture(),
                fn() => [
                    'days' => $this->start_time->diffInDays(now()),
                    'hours' => $this->start_time->diffInHours(now()),
                    'human' => $this->start_time->diffForHumans(),
                ]
            ),
        ];
    }
} 