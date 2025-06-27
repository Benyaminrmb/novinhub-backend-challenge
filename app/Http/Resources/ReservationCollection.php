<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReservationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'upcoming_count' => $this->collection->filter(function($reservation) {
                    return $reservation->timeSlot && $reservation->timeSlot->isFuture();
                })->count(),
                'completed_count' => $this->collection->filter(function($reservation) {
                    return $reservation->timeSlot && !$reservation->timeSlot->isFuture();
                })->count(),
            ],
        ];
    }
} 