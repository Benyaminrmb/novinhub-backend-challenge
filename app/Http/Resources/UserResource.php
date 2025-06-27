<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_consultant' => $this->isConsultant(),
            'is_client' => $this->isClient(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Conditional relationships
            'time_slots_count' => $this->when(
                $this->isConsultant() && $this->relationLoaded('timeSlots'),
                fn() => $this->timeSlots->count()
            ),
            'reservations_count' => $this->when(
                $this->isClient() && $this->relationLoaded('reservations'),
                fn() => $this->reservations->count()
            ),
            
            // Include related data when loaded
            'time_slots' => TimeSlotResource::collection($this->whenLoaded('timeSlots')),
            'reservations' => ReservationResource::collection($this->whenLoaded('reservations')),
        ];
    }
} 