<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsTimelineResource;
use App\Models\NewsTimeline;

class NewsTimelinesQuery
{
    public function handle($news_id) {
        $newsTimelines = NewsTimeline::where('news_id', $news_id)
            ->where('is_publish', true)
            ->orderBy('created_at', 'desc')
            ->select('title', 'details', 'image_path', 'image_caption', 'date')
            ->get();

        return NewsTimelineResource::collection($newsTimelines);
    }

}
