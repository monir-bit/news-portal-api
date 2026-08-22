<?php

namespace App\Services\News;

use App\Models\Category;

class CategoryIdsByChildRecursiveQuery
{
    /**
     * @param  array<int, string>  $categorySlugs
     * @return array<int, int>
     */
    public function handle(array $categorySlugs): array
    {
        $categoryIds = [];
        $categories = Category::whereIn('slug', $categorySlugs)->with('childrenRecursive')->get();

        $categories->each(function (Category $category) use (&$categoryIds) {
            $categoryIds[] = $category->id;
            if ($category->childrenRecursive) {
                $category->childrenRecursive->each(function (Category $child) use (&$categoryIds) {
                    $categoryIds[] = $child->id;
                });
            }
        });

        return $categoryIds;
    }
}
