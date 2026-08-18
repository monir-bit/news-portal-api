<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\SpecialSegment;
use App\Models\SpecialSegmentNews;

class SpecialSegmentNewsQuery
{
    public function handle($limit = 13) {
        $segment = SpecialSegment::with('tag')->where('is_active', true)->first();
        $news = [];

        if ($segment) {
            $news = SpecialSegmentNews::where('special_segment_id', $segment->id)
                ->orderBy('position', 'asc')
                ->whereHas('news', function ($query) {
                    $query->where('published', true);
                })
                ->with('news.category.parentRecursive')
                ->limit($limit)->get()->pluck('news');
        }

        return [
            'is_active' => $segment ? $segment->is_active : false,
            'info' => [
                'title' => $segment ? $segment->title : null,
                'desktop_banner_image' => $segment ? $segment->desktop_banner_image : null,
                'mobile_banner_image' => $segment ? $segment->mobile_banner_image : null,
            ],
            'news' => NewsListResource::collection($news),
            'tag' => $segment ? $segment->tag : null
        ];
    }

}
