<?php

namespace App\Applications\Queries\Api;

use App\Applications\Cache\CacheKey;
use App\Applications\Helpers\PortalDateHelper;
use App\Http\Resources\Api\NewsListResource;
use App\Models\Category;
use App\Models\News;
use App\Models\NewsRead;

class MostReadNewsByCategoryQuery
{
    public function handle(int $category_id, $limit = 15) {

        return \Cache::remember(CacheKey::mostReadNewsByCategory($category_id), now()->addMinutes(5), function () use ($category_id, $limit) {
            $category = Category::findOrFail($category_id);
            $children_category_ids = (new CategoryIdsByChildRecursiveQuery())->handle([$category->slug]);

            $mostReadIds = NewsRead::whereHas('news', function($nQ) {
                    $nQ->where('published', true)->whereBetween('date', [
                        PortalDateHelper::subDay(),
                        PortalDateHelper::now(),
                    ]);
                })->select('news_id')
                ->whereIn('category_id', $children_category_ids)
                ->groupBy('news_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit($limit)
                ->pluck('news_id');

            $news = News::query()
                ->whereIn('id', $mostReadIds)
                ->with('category.parentRecursive')
                ->get()
                // Sort news according to most-read order.
                ->sortBy(function ($news) use ($mostReadIds) {
                    return $mostReadIds->search($news->id);
                })
                ->values();

            return NewsListResource::collection($news);
        });
    }
}
