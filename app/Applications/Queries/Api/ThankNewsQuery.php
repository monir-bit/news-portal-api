<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class ThankNewsQuery
{
    public function handle()
    {
        $section_slug = 'thanks';

        return Cache::remember(CacheKey::homeSectionWiseNews($section_slug), now()->addMinutes(5), function () use ($section_slug) {
            $layout_section = LayoutSection::where('slug', $section_slug)->first();
            if (! $layout_section) {
                return [];
            }

            $section = LayoutSectionNews::with(['news.category.parentRecursive', 'news.thankNews', 'news.liveNews'])
                ->where('layout_section_id', $layout_section->id)
                ->whereHas('news', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('published', true);
                })
                ->orderBy('position', 'ASC')
                ->first();

            if (! $section || ! $section?->news) {
                return [];
            }

            return [
                'meta' => $section?->news?->thankNews,
                'news' => NewsListResource::make($section->news),
            ];
        });
    }
}
