<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\News;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class HeaderNewsQuery
{
    public function handle()
    {
        return app(SharedCache::class)->flexible(CacheKey::header(), [CacheTags::header()], function () {
            return [
                'news_1' => $this->getTagNews('স্পেশাল-১'),
                'news_2' => $this->getTagNews('স্পেশাল-২'),
                'news_3' => $this->getTagNews('স্পেশাল-৩'),
            ];
        }, [300, 900]);
    }

    public function getTagNews($tagSlug)
    {
        $news = News::query()
            ->select(array_map(fn ($column) => "news.{$column}", NewsListResource::NEWS_COLUMNS))
            ->join('news_tag_mappings', 'news_tag_mappings.news_id', '=', 'news.id')
            ->join('tags', 'tags.id', '=', 'news_tag_mappings.tag_id')
            ->where('news.published', true)
            ->where('tags.slug', $tagSlug)
            ->with([
                'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive.parent' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            ])
            ->latest('news.created_at')
            ->first();

        return $news ? NewsListResource::make($news)->resolve() : null;
    }
}
