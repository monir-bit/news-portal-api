<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\MarqueNews;
use App\Models\News;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class MarqueNewsQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function handle(): array
    {
        return $this->sharedCache->flexible(
            CacheKey::marque(),
            [CacheTags::marquee()],
            fn () => $this->build(),
            [300, 900],
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function build(): array
    {
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
    }
}
