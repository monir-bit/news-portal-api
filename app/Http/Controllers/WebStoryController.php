<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsListResource;
use App\Http\Resources\WebStorySliderDataResource;
use App\Models\News;
use App\Models\WebStory;
use App\Services\News\CategoryIdsByChildRecursiveQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class WebStoryController extends Controller
{
    public function sliderData(SharedCache $sharedCache): AnonymousResourceCollection
    {
        return $sharedCache->remember(
            CacheKey::webStorySliderDataHome(),
            [CacheTags::webStory()],
            function () {
                $data = WebStory::orderByDesc('created_at')
                    ->with(['items', 'news'])
                    ->limit(10)
                    ->get();

                return WebStorySliderDataResource::collection($data);
            },
            600,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function sliderDetails(string $hash_key, SharedCache $sharedCache): array
    {
        return $sharedCache->remember(
            CacheKey::make('web-story-details', ['hash' => $hash_key]),
            [CacheTags::webStory()],
            function () use ($hash_key) {
                $webStory = WebStory::orderByDesc('created_at')
                    ->with(['items:title,image,web_story_id', 'news.category.parentRecursive'])
                    ->limit(10)
                    ->where('hash_key', $hash_key)
                    ->firstOrFail();

                return [
                    'id' => $webStory->id,
                    'hash_key' => $webStory->hash_key,
                    'items' => $webStory->items,
                    'news' => NewsListResource::make($webStory->news),
                ];
            },
            600,
        );
    }

    public function sportsWebHistory(CategoryIdsByChildRecursiveQuery $categoryIdsByChildRecursiveQuery, SharedCache $sharedCache): AnonymousResourceCollection
    {
        return $sharedCache->remember(
            CacheKey::webStorySliderDataSports(),
            [CacheTags::webStory()],
            function () use ($categoryIdsByChildRecursiveQuery) {
                $sportsCategoryIds = $categoryIdsByChildRecursiveQuery->handle(['sports']);

                $data = News::whereHas('webStory')
                    ->whereIn('category_id', $sportsCategoryIds)
                    ->with(['webStory', 'webStory.items:title,image,web_story_id'])
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get()
                    ->pluck('webStory');

                return WebStorySliderDataResource::collection($data);
            },
            600,
        );
    }
}
