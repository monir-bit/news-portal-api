<?php

namespace App\Http\Controllers\Api\V2;

use App\Applications\Helpers\UtilsHelper;
use App\Enums\LayoutSectionEnum;
use App\Http\Controllers\Api\V2\Concerns\InteractsWithNewsRails;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use App\Models\SpecialSegment;
use App\Models\SpecialSegmentNews;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class HomeController extends Controller
{
    use InteractsWithNewsRails;

    public function initial(): array
    {
        $editorsPickSection = UtilsHelper::IsEnglishVersion() ? LayoutSectionEnum::EditorsPick->value : LayoutSectionEnum::FeatureBox->value;

        return [
            'trending_video_news' => $this->sectionNews(LayoutSectionEnum::TrendingVideoNews->value, 4),
            'lead_news' => $this->sectionNews(LayoutSectionEnum::LeadNews->value, 5),
            'world_cup_lead' => $this->sectionNews(LayoutSectionEnum::WorldCupLead->value, 5, livePinnedFirst: true),
            'pin_news' => $this->sectionNews(LayoutSectionEnum::PinNews->value, 4),
            'sub_lead_news' => $this->sectionNews(LayoutSectionEnum::SubLeadNews->value, 12),
            'editors_pick' => $this->sectionNews($editorsPickSection, 1),
            'latest_news' => $this->cachedLatestNews(),
            'most_read_news' => $this->cachedMostRead(),
            'special_segment_news' => $this->specialSegment(),
            'opinion' => $this->sectionNews(LayoutSectionEnum::Opinion->value, 1),
            'advice' => $this->sectionNews(LayoutSectionEnum::Advice->value, 1),
            'fact_check' => $this->sectionNews(LayoutSectionEnum::FactCheck->value, 1),
            'analysis' => $this->sectionNews(LayoutSectionEnum::Analysis->value, 1),
        ];
    }

    /**
     * News for a homepage layout section, in curated position order.
     * `livePinnedFirst` puts any currently-live news ahead of the curated order (world-cup lead rail).
     *
     * @return array<int, array{position: int, news: NewsListResource}>
     */
    private function sectionNews(string $sectionSlug, ?int $limit = null, bool $livePinnedFirst = false)
    {
        return Cache::flexible(CacheKey::homeSectionWiseNews($sectionSlug), [300, 900], function () use ($sectionSlug, $limit, $livePinnedFirst) {
            $sectionId = LayoutSection::where('slug', $sectionSlug)->value('id');

            if (! $sectionId) {
                return [];
            }

            return LayoutSectionNews::query()
                ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
                ->join('news', 'news.id', '=', 'layout_section_news.news_id')
                ->with([
                    'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                    'news.liveNews' => fn ($q) => $q->select(NewsListResource::LIVE_NEWS_COLUMNS),
                    'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->where('layout_section_news.layout_section_id', $sectionId)
                ->whereNull('news.deleted_at')
                ->where('news.published', true)
                ->when($livePinnedFirst, fn ($q) => $q->orderByDesc('news.live_news'))
                ->orderBy('layout_section_news.position')
                ->when($limit, fn ($q) => $q->limit($limit))
                ->get()
                ->filter(fn ($item) => $item->news !== null)
                ->map(fn ($item) => ['position' => $item->position, 'news' => NewsListResource::make($item->news)])
                ->values()
                ->all();
        });
    }

    /**
     * @return array{is_active: bool, info: array, news: array, tag: array|null}
     */
    private function specialSegment(int $limit = 13): array
    {
        // No CacheKey::* method exists for this one; build the same key format v1 uses
        // (config-driven prefix:version) so both versions share the cached entry.
        $prefix = config('shared-cache.prefix', 'news');
        $version = config('shared-cache.version', 'v1');
        $key = "{$prefix}:{$version}:special-segment:home:{$limit}";

        return Cache::flexible($key, [300, 900], function () use ($limit) {
            $segment = SpecialSegment::with('tag')->where('is_active', true)->first();

            $news = $segment
                ? SpecialSegmentNews::query()
                    ->select(['special_segment_news.id', 'special_segment_news.news_id', 'special_segment_news.position'])
                    ->join('news', 'news.id', '=', 'special_segment_news.news_id')
                    ->with([
                        'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                        'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                        'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                        'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    ])
                    ->where('special_segment_news.special_segment_id', $segment->id)
                    ->whereNull('news.deleted_at')
                    ->where('news.published', true)
                    ->orderBy('special_segment_news.position')
                    ->limit($limit)
                    ->get()
                    ->pluck('news')
                    ->filter()
                : collect();

            return [
                'is_active' => (bool) $segment?->is_active,
                'info' => [
                    'title' => $segment?->title,
                    'desktop_banner_image' => $segment?->desktop_banner_image,
                    'mobile_banner_image' => $segment?->mobile_banner_image,
                ],
                'news' => NewsListResource::collection($news)->resolve(),
                'tag' => $segment?->tag?->toArray(),
            ];
        });
    }
}
