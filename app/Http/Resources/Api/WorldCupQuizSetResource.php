<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldCupQuizSetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image_url' => $this->image,
            'is_active' => (bool) $this->is_active,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'quizzes' => WorldCupQuizResource::collection(
                $this->relationLoaded('activeQuizzes')
                    ? $this->activeQuizzes
                    : ($this->relationLoaded('quizzes') ? $this->quizzes : collect())
            ),
        ];
    }
}
