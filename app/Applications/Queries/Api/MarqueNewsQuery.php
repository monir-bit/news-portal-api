<?php

namespace App\Applications\Queries\Api;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Http\Resources\Api\NewsListResource;
use App\Models\MarqueNews;
use App\Models\News;
use Illuminate\Support\Facades\Cache;

class MarqueNewsQuery
{
    public function handle() {
        return Cache::remember(CacheKey::marque(), now()->addMinutes(5), function () {
            $marque_news_id = MarqueNews::pluck('news_id');
            $news = News::whereIn('id', $marque_news_id)
                ->orderByDesc('date')
                ->where('published', true)
                ->with('category.parentRecursive')
                ->limit(10)
                ->get();
            return NewsListResource::collection($news);
        });
    }

}
