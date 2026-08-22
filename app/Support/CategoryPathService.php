<?php

namespace App\Support;

use App\Models\Category;

class CategoryPathService
{
    public function build(Category $category): string
    {
        $segments = [];

        while ($category) {
            $segments[] = $category->slug;
            $category = $this->nextParent($category);
        }

        return implode('/', array_reverse($segments));
    }

    /**
     * Walk the same relation the app typically eager-loads (`parentRecursive`),
     * so `build()` does not re-query `parent` when only `parentRecursive` was loaded.
     */
    private function nextParent(Category $category): ?Category
    {
        if ($category->relationLoaded('parentRecursive')) {
            return $category->parentRecursive;
        }
        if ($category->relationLoaded('parent')) {
            return $category->parent;
        }

        return $category->parentRecursive;
    }
}
