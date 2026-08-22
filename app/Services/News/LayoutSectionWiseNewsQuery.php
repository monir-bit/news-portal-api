<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use Illuminate\Support\Collection;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class LayoutSectionWiseNewsQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return Collection<int, array{position: int, news: NewsListResource}>|array<int, never>
     */
    public function handle(string $sectionSlug, ?int $limit = null): Collection|array
    {
        return $this->sharedCache->flexible(
            CacheKey::homeSectionWiseNews($sectionSlug),
            [CacheTags::section($sectionSlug)],
            fn () => $this->buildHandle($sectionSlug, $limit),
            [300, 900],
        );
    }

    /**
     * @return Collection<int, array{position: int, news: NewsListResource}>|array<int, never>
     */
    private function buildHandle(string $sectionSlug, ?int $limit): Collection|array
    {
        $layoutSection = LayoutSection::where('slug', $sectionSlug)->select('id')->first();
        if (! $layoutSection) {
            return [];
        }

        return LayoutSectionNews::query()
            ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
            ->join('news', 'news.id', '=', 'layout_section_news.news_id')
            ->with($this->newsEagerLoads())
            ->where('layout_section_news.layout_section_id', $layoutSection->id)
            ->whereNull('news.deleted_at')
            ->where('news.published', true)
            ->when($limit, fn ($q, $limit) => $q->limit($limit))
            ->orderBy('layout_section_news.position', 'ASC')
            ->get()
            ->map(fn ($item) => [
                'position' => $item->position,
                'news' => NewsListResource::make($item->news),
            ]);
    }

    /**
     * @return Collection<int, array{position: int, news: NewsListResource}>|array<int, never>
     */
    public function handleLivePin(string $sectionSlug, ?int $limit = null): Collection|array
    {
        return $this->sharedCache->flexible(
            CacheKey::homeSectionWiseNews($sectionSlug),
            [CacheTags::section($sectionSlug)],
            fn () => $this->buildHandleLivePin($sectionSlug, $limit),
            [300, 900],
        );
    }

    /**
     * @return Collection<int, array{position: int, news: NewsListResource}>|array<int, never>
     */
    private function buildHandleLivePin(string $sectionSlug, ?int $limit): Collection|array
    {
        $layoutSection = LayoutSection::where('slug', $sectionSlug)->select('id')->first();
        if (! $layoutSection) {
            return [];
        }

        return LayoutSectionNews::query()
            ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
            ->join('news', 'news.id', '=', 'layout_section_news.news_id')
            ->with($this->newsEagerLoads())
            ->where('layout_section_news.layout_section_id', $layoutSection->id)
            ->whereNull('news.deleted_at')
            ->where('news.published', true)
            ->orderByDesc('news.live_news')
            ->orderBy('layout_section_news.position', 'ASC')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get()
            ->map(fn ($item) => [
                'position' => $item->position,
                'news' => NewsListResource::make($item->news),
            ]);
    }

    /**
     * Shared, column-restricted eager loads for the `news.category.parentRecursive`
     * chain plus `news.liveNews` (no longer implicitly loaded by the `news()` relation).
     *
     * Current category tree depth is 3 levels (verified via DB), so two `parentRecursive`
     * hops cover every existing category. If the tree grows deeper, Eloquent falls back
     * to the relation's own unconstrained recursive eager load for the extra levels
     * instead of dropping them.
     *
     * @return array<string, \Closure>
     */
    private function newsEagerLoads(): array
    {
        return [
            'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
            'news.liveNews' => fn ($q) => $q->select(NewsListResource::LIVE_NEWS_COLUMNS),
            'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
        ];
    }
}
