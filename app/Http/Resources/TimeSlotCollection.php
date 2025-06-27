<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TimeSlotCollection extends ResourceCollection
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
                'available_count' => $this->collection->filter(fn($slot) => $slot->isAvailable())->count(),
                'reserved_count' => $this->collection->filter(fn($slot) => !$slot->isAvailable())->count(),
                'future_count' => $this->collection->filter(fn($slot) => $slot->isFuture())->count(),
                'available_future_count' => $this->collection->filter(fn($slot) => $slot->isAvailable() && $slot->isFuture())->count(),
            ],
        ];
    }
} 