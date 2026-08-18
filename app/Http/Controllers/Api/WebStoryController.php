<?php

namespace App\Http\Controllers\Api;

use App\Applications\Cache\CacheKey;
use App\Applications\Queries\Api\CategoryIdsByChildRecursiveQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NewsListResource;
use App\Http\Resources\Api\WebStorySliderDataResource;
use App\Models\News;
use App\Models\WebStory;
use Illuminate\Support\Facades\Cache;

class WebStoryController extends Controller
{
    public function sliderData()
    {
        return Cache::remember(CacheKey::webStorySliderDataHome(), now()->addMinutes(10), function () {
            $data = WebStory::orderBy('created_at', 'desc')
                ->with(['items', 'news'])
                ->limit(10)
                ->get();
            return WebStorySliderDataResource::collection($data);
        });
    }

    public function sliderDetails($has_key){
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
    }


    public function sportsWebHistory(CategoryIdsByChildRecursiveQuery $categoryIdsByChildRecursiveQuery)
    {
        return Cache::remember(CacheKey::webStorySliderDataSports(), now()->addMinutes(10), function () use ($categoryIdsByChildRecursiveQuery) {
            $sportsCategoryIds = $categoryIdsByChildRecursiveQuery->handle(['sports']);
            $data =  News::whereHas('webStory')
                ->whereIn('category_id', $sportsCategoryIds)
                ->with(['webStory', 'webStory.items:title,image,web_story_id'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()->pluck('webStory');

            return WebStorySliderDataResource::collection($data);
        });
    }
}
