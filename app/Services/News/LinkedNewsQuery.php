<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\LinkedNews;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LinkedNewsQuery
{
    public function handle(int $newsId): AnonymousResourceCollection
    {
        $news = LinkedNews::where('main_news_id', $newsId)
            ->whereHas('linkedArticle', fn ($news) => $news->where('published', true))
            ->with('linkedArticle.category.parentRecursive')
            ->orderByDesc('created_at')
            ->get()
            ->pluck('linkedArticle');

        return NewsListResource::collection($news);
    }
}
