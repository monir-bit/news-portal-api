<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebStorySliderDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstItem = $this->items?->first();

        return [
            'hash_key' => $this->hash_key,
            'image' => $firstItem?->image,
            'title' => $firstItem?->title ?? '',
        ];
    }
}
