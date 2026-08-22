<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class ThankNewsQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return $this->sharedCache->flexible(
            CacheKey::make('home-section-wise-news:thanks-block'),
            [CacheTags::section('thanks')],
            fn () => $this->build(),
            [300, 900],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $sectionSlug = 'thanks';

        $layoutSection = LayoutSection::where('slug', $sectionSlug)->select('id')->first();
        if (! $layoutSection) {
            return [];
        }

        $section = LayoutSectionNews::query()
            ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
            ->join('news', 'news.id', '=', 'layout_section_news.news_id')
            ->with([
                'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                'news.liveNews' => fn ($q) => $q->select(NewsListResource::LIVE_NEWS_COLUMNS),
                'news.thankNews',
                'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            ])
            ->where('layout_section_news.layout_section_id', $layoutSection->id)
            ->whereNull('news.deleted_at')
            ->where('news.published', true)
            ->orderBy('layout_section_news.position', 'ASC')
            ->first();

        if (! $section || ! $section->news) {
            return [];
        }

        return [
            'meta' => $section->news->thankNews?->toArray(),
            'news' => NewsListResource::make($section->news)->resolve(),
        ];
    }
}
