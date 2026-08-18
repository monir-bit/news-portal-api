<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldCupQuizResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'image_url' => $this->image,
            'duration_seconds' => (int) $this->duration_seconds,
            'sort_order' => (int) $this->sort_order,
            'options' => WorldCupQuizOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
