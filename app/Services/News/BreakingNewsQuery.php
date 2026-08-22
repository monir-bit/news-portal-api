<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\BreakingNews;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class BreakingNewsQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return list<array{title: string, hash: string, url: string|null}>
     */
    public function handle(): array
    {
        return $this->sharedCache->flexible(
            CacheKey::breakingNews(),
            [CacheTags::breakingNews()],
            fn () => $this->build(),
            [300, 900],
        );
    }

    /**
     * @return list<array{title: string, hash: string, url: string|null}>
     */
    private function build(): array
    {
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
    }
}
