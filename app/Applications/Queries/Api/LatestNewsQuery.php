<?php

namespace App\Applications\Queries\Api;

use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Applications\Helpers\PortalDateHelper;
use App\Http\Resources\Api\NewsListResource;
use App\Models\LatestNews;
use Illuminate\Support\Facades\Cache;

class LatestNewsQuery
{
    public function handle($offset = 0, $limit = 15)
    {
        $today = PortalDateHelper::todayDateString();

        return Cache::remember(CacheKey::siteLatestNews($today), now()->addMinutes(3), function () use ($offset, $limit) {
            $news = LatestNews::query()
                ->whereHas('news', fn ($news) => $news
                    ->where('published', true)
                    ->whereBetween('date', [
                        PortalDateHelper::todayStart(),
                        PortalDateHelper::todayEnd(),
                    ]))
                ->with('news.category.parentRecursive')
                ->join('news', 'latest_news.news_id', '=', 'news.id')
                ->orderByDesc('news.date')
                ->select('latest_news.*')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->pluck('news');

            return NewsListResource::collection($news);
        });
    }

}
