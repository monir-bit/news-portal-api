<?php

namespace App\Applications\Queries\Api;

use App\Applications\Helpers\PortalDateHelper;
use App\Http\Resources\Api\NewsListResource;
use App\Models\LatestNews;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class LatestNewsQuery
{
    public function handle($offset = 0, $limit = 15)
    {
        $today = PortalDateHelper::todayDateString();

        return app(SharedCache::class)->remember(CacheKey::siteLatestNews($today), [CacheTags::latestNews()], function () use ($offset, $limit) {
            $news = LatestNews::query()
                ->select(['latest_news.id', 'latest_news.news_id', 'latest_news.position'])
                ->join('news', 'latest_news.news_id', '=', 'news.id')
                ->with([
                    'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                    'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->where('news.published', true)
                ->whereBetween('news.date', [
                    PortalDateHelper::todayStart(),
                    PortalDateHelper::todayEnd(),
                ])
                ->orderByDesc('news.date')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->pluck('news');

            return NewsListResource::collection($news)->resolve();
        }, 180);
    }
}
