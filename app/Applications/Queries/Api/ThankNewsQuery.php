<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class ThankNewsQuery
{
    public function handle()
    {
        $section_slug = 'thanks';

        $cacheKey = CacheKey::make('home-section-wise-news:thanks-block');

        return app(SharedCache::class)->flexible($cacheKey, [CacheTags::section($section_slug)], function () use ($section_slug) {
            $layout_section = LayoutSection::where('slug', $section_slug)->select('id')->first();
            if (! $layout_section) {
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
                ->where('layout_section_news.layout_section_id', $layout_section->id)
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
        }, [300, 900]);
    }
}
