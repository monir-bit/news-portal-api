<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NewsListResource;
use App\Http\Resources\Api\WebStorySliderDataResource;
use App\Models\Category;
use App\Models\News;
use App\Models\WebStory;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class WebStoryController extends Controller
{
    public function sliderData(): mixed
    {
        return Cache::remember(CacheKey::webStorySliderDataHome(), now()->addMinutes(10), function () {
            $data = WebStory::orderByDesc('created_at')
                ->with(['items', 'news'])
                ->limit(10)
                ->get();

            return WebStorySliderDataResource::collection($data);
        });
    }

    /**
     * @return array{id: int, hash_key: string, items: mixed, news: NewsListResource}
     */
    public function sliderDetails(string $hashKey): array
    {
        $webStory = WebStory::orderByDesc('created_at')
            ->with(['items:title,image,web_story_id', 'news.category.parentRecursive'])
            ->limit(10)
            ->where('hash_key', $hashKey)
            ->firstOrFail();

        return [
            'id' => $webStory->id,
            'hash_key' => $webStory->hash_key,
            'items' => $webStory->items,
            'news' => NewsListResource::make($webStory->news),
        ];
    }

    /**
     * Recent web stories in the "sports" category tree (and its descendants).
     *
     * Reuses `Category::idsForSlug()` instead of a dedicated recursive-ids
     * query class (v1's `CategoryIdsByChildRecursiveQuery` computed the same
     * "category id + all descendant ids" set for a single slug).
     */
    public function sportsWebHistory(): mixed
    {
        return Cache::remember(CacheKey::webStorySliderDataSports(), now()->addMinutes(10), function () {
            $data = News::whereHas('webStory')
                ->whereIn('category_id', Category::idsForSlug('sports'))
                ->with(['webStory', 'webStory.items:title,image,web_story_id'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->pluck('webStory');

            return WebStorySliderDataResource::collection($data);
        });
    }
}
