<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class LayoutSectionWiseNewsQuery
{
    /**
     * Columns required by NewsListResource (and its "date" fallback to created_at).
     *
     * @var array<int, string>
     */
    private const NEWS_COLUMNS = [
        'id', 'category_id', 'slug_key', 'title', 'ticker', 'image', 'image_caption',
        'shoulder', 'sort_description', 'live_news', 'is_thread', 'is_visible_shoulder',
        'is_visible_ticker', 'date', 'created_at', 'representative',
    ];

    /**
     * Columns required by CategoryListResource / CategoryPathService for every
     * level of the category -> parentRecursive -> parentRecursive chain.
     *
     * @var array<int, string>
     */
    private const CATEGORY_COLUMNS = ['id', 'name', 'slug', 'parent_id'];

    /**
     * Columns required by NewsListResource's whenLoaded('liveNews', ...) check.
     *
     * @var array<int, string>
     */
    private const LIVE_NEWS_COLUMNS = ['id', 'news_id', 'is_active'];

    public function handle(string $section_slug, $limit = null)
    {
        return Cache::flexible(CacheKey::homeSectionWiseNews($section_slug), [300, 900], function () use ($section_slug, $limit) {
            $layout_section = LayoutSection::where('slug', $section_slug)->select('id')->first();
            if (! $layout_section) {
                return [];
            }

            $news_list = LayoutSectionNews::query()
                ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
                ->join('news', 'news.id', '=', 'layout_section_news.news_id')
                ->with($this->newsEagerLoads())
                ->where('layout_section_news.layout_section_id', $layout_section->id)
                ->whereNull('news.deleted_at')
                ->where('news.published', true)
                ->when($limit, function ($q, $limit) {
                    $q->limit($limit);
                })
                ->orderBy('layout_section_news.position', 'ASC')
                ->get()->map(function ($item) {
                    return [
                        'position' => $item->position,
                        'news' => NewsListResource::make($item->news),
                    ];
                });

            return $news_list;
        });
    }

    public function handleLivePin(string $section_slug, $limit = null)
    {
        return Cache::flexible(CacheKey::homeSectionWiseNews($section_slug), [300, 900], function () use ($section_slug, $limit) {
            $layout_section = LayoutSection::where('slug', $section_slug)->select('id')->first();

            if (! $layout_section) {
                return [];
            }

            $news_list = LayoutSectionNews::query()
                ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
                ->join('news', 'news.id', '=', 'layout_section_news.news_id')
                ->with($this->newsEagerLoads())
                ->where('layout_section_news.layout_section_id', $layout_section->id)
                ->whereNull('news.deleted_at')
                ->where('news.published', true)
                ->orderByDesc('news.live_news')     // live_news = 1 আগে
                ->orderBy('layout_section_news.position', 'ASC') // তারপর position
                ->when($limit, fn ($q) => $q->limit($limit))
                ->get()
                ->map(function ($item) {
                    return [
                        'position' => $item->position,
                        'news' => NewsListResource::make($item->news),
                    ];
                });

            return $news_list;
        });

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
            'news' => fn ($q) => $q->select(self::NEWS_COLUMNS),
            'news.liveNews' => fn ($q) => $q->select(self::LIVE_NEWS_COLUMNS),
            'news.category' => fn ($q) => $q->select(self::CATEGORY_COLUMNS),
            'news.category.parentRecursive' => fn ($q) => $q->select(self::CATEGORY_COLUMNS),
            'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(self::CATEGORY_COLUMNS),
        ];
    }
}
