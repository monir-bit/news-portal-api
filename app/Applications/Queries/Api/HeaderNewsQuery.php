<?php

namespace App\Applications\Queries\Api;

use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Http\Resources\Api\NewsListResource;
use App\Models\News;
use Illuminate\Support\Facades\Cache;

class HeaderNewsQuery
{
    public function handle() {
        return Cache::remember(CacheKey::header(), now()->addMinutes(5), function () {
            return [
                'news_1' => $this->getTagNews('স্পেশাল-১'),
                'news_2' => $this->getTagNews('স্পেশাল-২'),
                'news_3' => $this->getTagNews('স্পেশাল-৩')
            ];
        });
    }

    public function getTagNews($tagSlug)
    {
        $news = News::where('published', true)
            ->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            })
            ->with(['category.parentRecursive.parent'])
            ->latest()
            ->first();
        if($news){
            return NewsListResource::make($news);
        }
        return null;
    }
}
