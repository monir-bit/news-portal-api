<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\LinkedNews;

class LinkedNewsQuery
{
    public function handle($news_id) {
        $news = LinkedNews::where('main_news_id', $news_id)
            ->whereHas('linkedArticle', fn ($news) => $news->where('published', true))
            ->with('linkedArticle.category.parentRecursive')
            ->orderByDesc('created_at')
            ->get()
            ->pluck('linkedArticle');

        return NewsListResource::collection($news);
    }

}
