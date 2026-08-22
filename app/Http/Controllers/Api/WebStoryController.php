<?php

namespace App\Http\Controllers\Api;

use App\Applications\Queries\Api\CategoryIdsByChildRecursiveQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NewsListResource;
use App\Http\Resources\Api\WebStorySliderDataResource;
use App\Models\News;
use App\Models\WebStory;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class WebStoryController extends Controller
{
    public function sliderData()
    {
        return app(SharedCache::class)->remember(CacheKey::webStorySliderDataHome(), [CacheTags::webStory()], function () {
            $data = WebStory::orderBy('created_at', 'desc')
                ->with(['items', 'news'])
                ->limit(10)
                ->get();

            return WebStorySliderDataResource::collection($data);
        }, 600);
    }

    public function sliderDetails($has_key)
    {
        return app(SharedCache::class)->remember(
            CacheKey::make('web-story-slider-details', ['slug' => $has_key]),
            [CacheTags::webStory()],
            function () use ($has_key) {
                $web_story = WebStory::orderBy('created_at', 'desc')
                    ->with(['items:title,image,web_story_id', 'news.category.parentRecursive'])
                    ->limit(10)
                    ->where('hash_key', $has_key)
                    ->firstOrFail();

                return [
                    'id' => $web_story->id,
                    'hash_key' => $web_story->hash_key,
                    'items' => $web_story->items,
                    'news' => NewsListResource::make($web_story->news),
                ];
            },
            600
        );
    }

    public function sportsWebHistory(CategoryIdsByChildRecursiveQuery $categoryIdsByChildRecursiveQuery)
    {
        return app(SharedCache::class)->remember(CacheKey::webStorySliderDataSports(), [CacheTags::webStory()], function () use ($categoryIdsByChildRecursiveQuery) {
            $sportsCategoryIds = $categoryIdsByChildRecursiveQuery->handle(['sports']);
            $data = News::whereHas('webStory')
                ->whereIn('category_id', $sportsCategoryIds)
                ->with(['webStory', 'webStory.items:title,image,web_story_id'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()->pluck('webStory');

            return WebStorySliderDataResource::collection($data);
        }, 600);
    }
}
