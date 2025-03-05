<?php

namespace Soranoiseki\BookGroup\Http\Resources\TaskPlan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskInfoResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'group_id' => $this->group_id,
            'week1' => $this->week1,
            'week2' => $this->week2,
            'week3' => $this->week3,
            'week4' => $this->week4,
            'week5' => $this->week5,
        ];
    }
}
