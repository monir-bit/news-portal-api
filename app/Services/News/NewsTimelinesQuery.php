<?php

namespace App\Services\News;

use App\Http\Resources\NewsTimelineResource;
use App\Models\NewsTimeline;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NewsTimelinesQuery
{
    public function handle(int $newsId): AnonymousResourceCollection
    {
        $newsTimelines = NewsTimeline::where('news_id', $newsId)
            ->where('is_publish', true)
            ->orderByDesc('created_at')
            ->select('title', 'details', 'image_path', 'image_caption', 'date')
            ->get();

        return NewsTimelineResource::collection($newsTimelines);
    }
}
