<?php

namespace App\Applications\Queries\Api;

use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Applications\Helpers\PortalDateHelper;
use App\Http\Resources\Api\NewsListResource;
use App\Models\News;
use App\Models\NewsRead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MostReadNewsQuery
{
    public function handle() {
        $readDate = PortalDateHelper::todayDateString();

        return Cache::remember(CacheKey::siteMostReadNews($readDate), now()->addMinutes(3), function () {
            $mostReadIds = NewsRead::whereHas('news', function($nQ) {
                $nQ->where('published', true)->whereBetween('date', [
                    PortalDateHelper::subDay(),
                    PortalDateHelper::now(),
                ]);
            })->select('news_id')
                ->groupBy('news_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(15)
                ->pluck('news_id');
            Log::info("Most read news IDs for details: " . $mostReadIds->implode(', '));
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
