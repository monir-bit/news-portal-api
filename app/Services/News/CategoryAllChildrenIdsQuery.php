<?php

namespace App\Services\News;

use App\Models\Category;

class CategoryAllChildrenIdsQuery
{
    /**
     * @return array<int, int>
     */
    public function handle(string $categorySlug): array
    {
        $category = Category::where('slug', $categorySlug)->with('childrenRecursive')->first();
        $categoryIds = [];

        if ($category) {
            $categoryIds = $this->getAllChildIds($category);
            $categoryIds[] = $category->id;
        }

        return $categoryIds;
    }

    /**
     * @return array<int, int>
     */
    private function getAllChildIds(Category $category): array
    {
        $ids = [];

        foreach ($category->childrenRecursive as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildIds($child));
        }

        return $ids;
    }
}
