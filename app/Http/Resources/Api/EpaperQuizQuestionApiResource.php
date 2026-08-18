<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EpaperQuizQuestionApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => null,
            'page_number' => $this->page_number,
            'publish_date' => $this->publish_date,
            'options' => $this->whenLoaded('options', function (): array {
                return EpaperQuizOptionApiResource::collection($this->options)->resolve();
            }),
        ];
    }
}
