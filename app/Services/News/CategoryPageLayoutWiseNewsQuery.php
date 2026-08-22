<?php

namespace App\Services\News;

use App\Http\Resources\NewsListResource;
use App\Models\CategoryPageLayout;
use App\Models\CategoryPageLayoutNews;

class CategoryPageLayoutWiseNewsQuery
{
    /**
     * Editorially-curated, position-ordered news for one category page layout
     * slot (e.g. 'lead', 'selected' under a category, or 'holud-jhor' under
     * the World Cup category).
     *
     * @return array<int, array{position: int, news: NewsListResource}>
     */
    public function handle(int $categoryId, string $layoutSlug, ?int $limit = null): array
    {
        $layout = CategoryPageLayout::where('category_id', $categoryId)
            ->where('slug', $layoutSlug)
            ->where('is_enable', true)
            ->first();

        if (! $layout) {
            return [];
        }

        $query = CategoryPageLayoutNews::with('news.category.parentRecursive')
            ->where('category_page_layout_id', $layout->id)
            ->whereHas('news', function ($q) {
                $q->where('published', true);
            })
            ->orderBy('position', 'ASC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn ($item) => [
                'position' => $item->position,
                'news' => NewsListResource::make($item->news),
            ])
            ->values()
            ->all();
    }
}
