<?php

namespace App\Http\Controllers\Api\V2\Concerns;

use App\Http\Resources\Api\NewsListResource;
use App\Models\LatestNews;
use App\Models\News;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

/**
 * Small, repeated "rail" queries (latest news, most-read news) shared by the
 * public v2 news/home endpoints. Kept as a trait instead of a service class
 * so callers stay plain controller methods. Reuses the same CacheKey entries
 * as v1, so both versions share a warm cache for identical data.
 */
trait InteractsWithNewsRails
{
    protected function cachedLatestNews(int $limit = 15): array
    {
        return Cache::remember(CacheKey::siteLatestNews(), now()->addMinutes(3), function () use ($limit) {
            return NewsListResource::collection(LatestNews::homepageList($limit))->resolve();
        });
    }

    protected function cachedMostRead(?int $categoryId = null, int $limit = 15): array
    {
        $key = $categoryId ? CacheKey::mostReadNewsByCategory($categoryId, $limit) : CacheKey::siteMostReadNews();
        $categoryIds = $categoryId ? [$categoryId] : null;

        return Cache::remember($key, now()->addMinutes(5), function () use ($categoryIds, $limit) {
            return NewsListResource::collection(News::mostRead($categoryIds, $limit))->resolve();
        });
    }
}
