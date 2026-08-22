<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\SpecialSegment;
use App\Models\SpecialSegmentNews;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class SpecialSegmentNewsQuery
{
    public function handle($limit = 13)
    {
        $cacheKey = CacheKey::make('special-segment-home', ['limit' => $limit]);

        return app(SharedCache::class)->flexible($cacheKey, [CacheTags::section('special-segment')], function () use ($limit) {
            $segment = SpecialSegment::with('tag')->where('is_active', true)->first();
            $news = [];

            if ($segment) {
                $news = SpecialSegmentNews::query()
                    ->select(['special_segment_news.id', 'special_segment_news.news_id', 'special_segment_news.position'])
                    ->join('news', 'news.id', '=', 'special_segment_news.news_id')
                    ->with([
                        'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                        'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                        'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                        'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    ])
                    ->where('special_segment_news.special_segment_id', $segment->id)
                    ->where('news.published', true)
                    ->orderBy('special_segment_news.position', 'asc')
                    ->limit($limit)
                    ->get()
                    ->pluck('news');
            }

            return [
                'is_active' => $segment ? $segment->is_active : false,
                'info' => [
                    'title' => $segment ? $segment->title : null,
                    'desktop_banner_image' => $segment ? $segment->desktop_banner_image : null,
                    'mobile_banner_image' => $segment ? $segment->mobile_banner_image : null,
                ],
                'news' => NewsListResource::collection($news)->resolve(),
                'tag' => $segment ? $segment->tag?->toArray() : null,
            ];
        }, [300, 900]);
    }
}
