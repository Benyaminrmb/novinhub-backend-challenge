<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'time_slot_id' => $this->time_slot_id,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Related data when loaded
            'user' => new UserResource($this->whenLoaded('user')),
            'time_slot' => new TimeSlotResource($this->whenLoaded('timeSlot')),
            
            // Computed fields from relationships
            'consultant_name' => $this->when(
                $this->relationLoaded('timeSlot') && $this->timeSlot->relationLoaded('consultant'),
                fn() => $this->timeSlot->consultant->name
            ),
            'client_name' => $this->when(
                $this->relationLoaded('user'),
                fn() => $this->user->name
            ),
            'appointment_time' => $this->when(
                $this->relationLoaded('timeSlot'),
                fn() => [
                    'start' => $this->timeSlot->start_time->toISOString(),
                    'end' => $this->timeSlot->end_time->toISOString(),
                    'duration_minutes' => $this->timeSlot->start_time->diffInMinutes($this->timeSlot->end_time),
                ]
            ),
            'status' => $this->when(
                $this->relationLoaded('timeSlot'),
                fn() => $this->timeSlot->isFuture() ? 'upcoming' : 'completed'
            ),
        ];
    }
} 