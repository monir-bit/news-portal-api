<?php

namespace App\Http\Resources;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsSearchSuggestionResource extends JsonResource
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
            'slug_key' => $this->slug_key,
            'url' => $this->whenLoaded('category', function ($category) {
                return config('app.website_url').UtilsHelper::NewsUrl($category, $this->slug_key);
            }),
            'title' => $this->title,
            'image' => $this->image,
            'shoulder' => $this->shoulder,
            'sort_description' => $this->sort_description,
            'live_news' => $this->live_news,
            'date' => UtilsHelper::ToBanglaDate($this->date ?? $this->created_at),
        ];
    }
}
