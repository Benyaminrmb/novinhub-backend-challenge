<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
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
                'consultants_count' => $this->collection->filter(fn($user) => $user->isConsultant())->count(),
                'clients_count' => $this->collection->filter(fn($user) => $user->isClient())->count(),
            ],
        ];
    }
} 