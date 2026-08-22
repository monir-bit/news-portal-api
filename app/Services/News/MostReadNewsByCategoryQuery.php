<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\Category;
use App\Models\News;
use App\Models\NewsRead;
use App\Support\PortalDateHelper;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class MostReadNewsByCategoryQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function handle(int $category_id, int $limit = 15): array
    {
        return $this->sharedCache->remember(
            CacheKey::mostReadNewsByCategory($category_id, $limit),
            [CacheTags::category($category_id), CacheTags::mostRead()],
            fn () => $this->build($category_id, $limit),
            300,
        );
    }

    /**
     * @return array<int, mixed>
     */
    private function build(int $category_id, int $limit): array
    {
        $category = Category::findOrFail($category_id);
        $childrenCategoryIds = (new CategoryIdsByChildRecursiveQuery)->handle([$category->slug]);

        $mostReadIds = NewsRead::query()
            ->whereHas('news', function ($nQ) {
                $nQ->where('published', true)->whereBetween('date', [
                    PortalDateHelper::subDay(),
                    PortalDateHelper::now(),
                ]);
            })
            ->select('news_id')
            ->whereIn('category_id', $childrenCategoryIds)
            ->groupBy('news_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('news_id');

        // Column-restricted select + eager load, matching the MostReadNewsQuery /
        // LatestNewsQuery pattern (avoids overfetching full News rows and every
        // category column at every recursive depth).
        $news = News::query()
            ->select(NewsListResource::NEWS_COLUMNS)
            ->whereIn('id', $mostReadIds)
            ->with([
                'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            ])
            ->get()
            ->sortBy(fn ($news) => $mostReadIds->search($news->id))
            ->values();

        return NewsListResource::collection($news)->resolve();
    }
}
