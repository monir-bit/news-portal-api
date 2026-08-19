<?php

namespace App\Http\Resources\Api;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsListResource extends JsonResource
{
    /**
     * Columns this resource reads from the News model (plus the `date` -> created_at fallback).
     * Query classes select-restricting `news` for this resource must use this list.
     *
     * @var array<int, string>
     */
    public const NEWS_COLUMNS = [
        'id', 'category_id', 'slug_key', 'title', 'ticker', 'image', 'image_caption',
        'shoulder', 'sort_description', 'live_news', 'is_thread', 'is_visible_shoulder',
        'is_visible_ticker', 'date', 'created_at', 'representative',
    ];

    /**
     * Columns needed by CategoryListResource / CategoryPathService, for every depth of
     * the category -> parentRecursive chain.
     *
     * @var array<int, string>
     */
    public const CATEGORY_COLUMNS = ['id', 'name', 'slug', 'parent_id'];

    /**
     * Columns needed for the whenLoaded('liveNews', ...) check below.
     *
     * @var array<int, string>
     */
    public const LIVE_NEWS_COLUMNS = ['id', 'news_id', 'is_active'];

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
