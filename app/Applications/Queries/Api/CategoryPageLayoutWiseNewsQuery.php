<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\CategoryPageLayout;
use App\Models\CategoryPageLayoutNews;

class CategoryPageLayoutWiseNewsQuery
{
    /**
     * Get news for a category page layout section.
     *
     * @param  int  $categoryId  Category ID
     * @param  string  $layoutSlug  Layout slug (e.g. 'lead', 'sublead', 'selected')
     * @param  int|null  $limit  Optional limit
     * @return array<int, array{position: int, news: NewsListResource}>
     */
    public function handle(int $categoryId, string $layoutSlug, ?int $limit = null): array
    {
        $layout = CategoryPageLayout::where('category_id', $categoryId)
            ->where('slug', $layoutSlug)
            ->where('is_enable', true)
            ->first();

        if (!$layout) {
            return [];
        }

        $query = CategoryPageLayoutNews::with('news.category.parentRecursive')
            ->where('category_page_layout_id', $layout->id)
            ->whereHas('news', function ($q) {
                $q->whereNull('deleted_at');
                $q->where('published', true);
            })
            ->orderBy('position', 'ASC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $newsList = $query->get()->map(function ($item) {
            return [
                'position' => $item->position,
                'news' => NewsListResource::make($item->news),
            ];
        });

        return $newsList->values()->all();
    }
}
