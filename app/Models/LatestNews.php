<?php

namespace App\Models;

use App\Applications\Helpers\PortalDateHelper;
use App\Http\Resources\Api\NewsListResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LatestNews extends Model
{
    protected $fillable = [
        'news_id',
        'position',
    ];

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Curated "latest news" rail for the homepage: published news dated today,
     * in curated (`position`) order.
     */
    public static function homepageList(int $limit = 15): Collection
    {
        return static::query()
            ->select(['latest_news.id', 'latest_news.news_id', 'latest_news.position'])
            ->join('news', 'latest_news.news_id', '=', 'news.id')
            ->with([
                'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            ])
            ->where('news.published', true)
            ->whereBetween('news.date', [PortalDateHelper::todayStart(), PortalDateHelper::todayEnd()])
            ->orderByDesc('news.date')
            ->limit($limit)
            ->get()
            ->pluck('news');
    }
}
