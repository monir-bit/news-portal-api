<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\LatestNews;
use App\Support\PortalDateHelper;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class LatestNewsQuery
{
    /**
     * Default offset/limit is the only combination any call site uses today
     * (the site-wide "latest news" rail). Only that combination is cached,
     * under the date-keyed CacheKey::siteLatestNews() key - a future caller
     * requesting a different slice bypasses the cache instead of colliding
     * with it on the same key.
     */
    private const CACHED_OFFSET = 0;

    private const CACHED_LIMIT = 15;

    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function handle(int $offset = 0, int $limit = 15): array
    {
        if ($offset === self::CACHED_OFFSET && $limit === self::CACHED_LIMIT) {
            return $this->sharedCache->remember(
                CacheKey::siteLatestNews(),
                [CacheTags::latestNews()],
                fn () => $this->build($offset, $limit),
                180,
            );
        }

        return $this->build($offset, $limit);
    }

    /**
     * @return array<int, mixed>
     */
    private function build(int $offset, int $limit): array
    {
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
    }
}
