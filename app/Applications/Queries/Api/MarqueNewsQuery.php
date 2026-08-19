<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\MarqueNews;
use App\Models\News;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class MarqueNewsQuery
{
    public function handle()
    {
        return Cache::remember(CacheKey::marque(), now()->addMinutes(5), function () {
            $news = News::query()
                ->select(NewsListResource::NEWS_COLUMNS)
                ->whereIn('id', MarqueNews::query()->select('news_id'))
                ->where('published', true)
                ->with([
                    'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->orderByDesc('date')
                ->limit(10)
                ->get();

            return NewsListResource::collection($news)->resolve();
        });
    }
}
