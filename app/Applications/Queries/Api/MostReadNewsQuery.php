<?php

namespace App\Applications\Queries\Api;

use App\Applications\Helpers\PortalDateHelper;
use App\Http\Resources\Api\NewsListResource;
use App\Models\News;
use App\Models\NewsRead;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class MostReadNewsQuery
{
    public function handle()
    {
        $readDate = PortalDateHelper::todayDateString();

        return Cache::remember(CacheKey::siteMostReadNews($readDate), now()->addMinutes(3), function () {
            $mostReadIds = NewsRead::query()
                ->select('news_reads.news_id')
                ->join('news', 'news.id', '=', 'news_reads.news_id')
                ->where('news.published', true)
                ->whereBetween('news.date', [
                    PortalDateHelper::subDay(),
                    PortalDateHelper::now(),
                ])
                ->groupBy('news_reads.news_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(15)
                ->pluck('news_reads.news_id');

            $news = News::query()
                ->select(NewsListResource::NEWS_COLUMNS)
                ->whereIn('id', $mostReadIds)
                ->with([
                    'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->get()
                // Sort news according to most-read order.
                ->sortBy(function ($news) use ($mostReadIds) {
                    return $mostReadIds->search($news->id);
                })
                ->values();

            return NewsListResource::collection($news)->resolve();
        });
    }
}
