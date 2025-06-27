<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
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
            'consultant_id' => $this->consultant_id,
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time->toISOString(),
            'is_available' => $this->isAvailable(),
            'is_future' => $this->isFuture(),
            'duration_minutes' => $this->start_time->diffInMinutes($this->end_time),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Related data when loaded
            'consultant' => new UserResource($this->whenLoaded('consultant')),
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            
            // Additional computed fields
            'status' => $this->when(
                true,
                fn() => $this->isAvailable() 
                    ? ($this->isFuture() ? 'available' : 'expired')
                    : 'reserved'
            ),
        ];
    }
} 