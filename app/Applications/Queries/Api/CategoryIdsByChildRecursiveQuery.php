<?php

namespace App\Applications\Queries\Api;

use App\Models\Category;

class CategoryIdsByChildRecursiveQuery
{
    public function handle(array $categorySlugs): array {
        $sportsCategoryIds = [];
        $categories = Category::whereIn('slug', $categorySlugs)->with('childrenRecursive')->get();
        $categories->each(function ($category) use (&$sportsCategoryIds) {
            $sportsCategoryIds[] = $category->id;
            if ($category->childrenRecursive) {
                $category->childrenRecursive->each(function ($child) use (&$sportsCategoryIds) {
                    $sportsCategoryIds[] = $child->id;
                });
            }
        });
        return $sportsCategoryIds;
    }

}
