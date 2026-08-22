<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\SpecialSegment;
use App\Models\SpecialSegmentNews;

class SpecialSegmentNewsQuery
{
    /**
     * @return array<string, mixed>
     */
    public function handle(int $limit = 13): array
    {
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
    }
}
