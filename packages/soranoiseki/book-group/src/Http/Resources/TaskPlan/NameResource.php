<?php

namespace Soranoiseki\BookGroup\Http\Resources\TaskPlan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NameResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'role' => $this->role,
            'names' => $this->names,
        ];
    }
}
