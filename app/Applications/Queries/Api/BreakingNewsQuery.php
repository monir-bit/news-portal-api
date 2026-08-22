<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\BreakingNews;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class BreakingNewsQuery
{
    /**
     * @return list<array{title: string, hash: string, url: string|null}>
     */
    public function handle(): array
    {
        return app(SharedCache::class)->flexible(CacheKey::breakingNews(), [CacheTags::breakingNews()], function () {
            $rows = BreakingNews::query()
                ->where('published', true)
                ->with([
                    'news' => fn ($q) => $q->select(['id', 'category_id', 'slug_key']),
                    'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive.parent' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $request = request();

            return $rows->map(function (BreakingNews $bn) use ($request) {
                $url = null;
                if ($bn->news !== null) {
                    $payload = (new NewsListResource($bn->news))->toArray($request);
                    $url = $payload['url'] ?? null;
                }

                return [
                    'title' => $bn->title,
                    'hash' => $bn->hash,
                    'url' => $url,
                ];
            })->values()->all();
        }, [300, 900]);
    }
}
