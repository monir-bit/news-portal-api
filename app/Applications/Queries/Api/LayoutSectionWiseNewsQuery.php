<?php

namespace App\Applications\Queries\Api;

use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Http\Resources\Api\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use Illuminate\Support\Facades\Cache;

class LayoutSectionWiseNewsQuery
{
    public function handle(string $section_slug, $limit = null){
        return Cache::remember(CacheKey::homeSectionWiseNews($section_slug), now()->addMinutes(5), function () use ($section_slug, $limit) {
            $layout_section = LayoutSection::where('slug', $section_slug)->first();
            if(!$layout_section) {
                return [];
            }

            $news_list = LayoutSectionNews::with(['news.category.parentRecursive'])
                ->where('layout_section_id', $layout_section->id)
                ->whereHas('news', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('published', true);
                })
                ->when($limit, function ($q, $limit) {
                    $q->limit($limit);
                })
                ->orderBy('position', 'ASC')
                ->get()->map(function ($item) {
                    return [
                        'position' => $item->position,
                        'news' => NewsListResource::make($item->news),
                    ];
                });


            return $news_list;
        });
    }

    public function handleLivePin(string $section_slug, $limit = null){
        return Cache::remember(CacheKey::homeSectionWiseNews($section_slug), now()->addMinutes(5), function () use ($section_slug, $limit) {
            $layout_section = LayoutSection::where('slug', $section_slug)->first();

            if (!$layout_section) {
                return [];
            }

            $news_list = LayoutSectionNews::query()
                ->select('layout_section_news.*')
                ->join('news', 'news.id', '=', 'layout_section_news.news_id')
                ->with(['news.category.parentRecursive'])
                ->where('layout_section_id', $layout_section->id)
                ->whereNull('news.deleted_at')
                ->where('news.published', true)
                ->orderByDesc('news.live_news')     // live_news = 1 আগে
                ->orderBy('layout_section_news.position', 'ASC') // তারপর position
                ->when($limit, fn ($q) => $q->limit($limit))
                ->get()
                ->map(function ($item) {
                    return [
                        'position' => $item->position,
                        'news' => NewsListResource::make($item->news),
                    ];
                });

            return $news_list;
        });

    }
}
