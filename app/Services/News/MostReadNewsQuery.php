<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\News;
use App\Models\NewsRead;
use App\Support\PortalDateHelper;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class MostReadNewsQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function handle(): array
    {
        return $this->sharedCache->remember(
            CacheKey::siteMostReadNews(),
            [CacheTags::mostRead()],
            fn () => $this->build(),
            180,
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function build(): array
    {
        $mostReadIds = NewsRead::query()
            ->select('news_reads.news_id')
            ->join('news', 'news.id', '=', 'news_reads.news_id')
            ->where('news.published', true)
            ->whereBetween('news.date', [
                PortalDateHelper::subDay(),
                PortalDateHelper::now(),
            ])
            ->groupBy('news_reads.news_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('news_reads.news_id');

        $news = News::query()
            ->select(NewsListResource::NEWS_COLUMNS)
            ->whereIn('id', $mostReadIds)
            ->with([
                'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            ])
            ->get()
            ->sortBy(fn ($news) => $mostReadIds->search($news->id))
            ->values();

        return NewsListResource::collection($news)->resolve();
    }
}
