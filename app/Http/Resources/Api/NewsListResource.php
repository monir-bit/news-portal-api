<?php

namespace App\Http\Resources\Api;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $url = $this->whenLoaded('category', function ($category) {
            return UtilsHelper::NewsUrl($category, $this->slug_key);
        });
        return [
            'slug' => $this->slug_key,
            'url' => $url,
            'is_photo' => str_contains($url, 'photo'),
            'title' => $this->title,
            'ticker' => $this->ticker,
            'image' => $this->image,
            'image_caption' => $this->image_caption,
            'shoulder' => $this->shoulder,
            'sort_description' => $this->sort_description,
            'live_news' => $this->whenLoaded('liveNews', function ($liveNews) {
                return $this->live_news && $liveNews->is_active;
            }),
            'is_thread' => $this->is_thread,
            'is_visible_shoulder' => $this->is_visible_shoulder,
            'is_visible_ticker' => $this->is_visible_ticker,
            'date' => ($this->date ?? $this->created_at)?->toIso8601String(),
            'category' => $this->whenLoaded('category', function ($category) {
                return CategoryListResource::make($category)->resolve();
            }),
            'representative' => $this->representative,
        ];
    }
}
// agamir#somoy26
