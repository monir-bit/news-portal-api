<?php

namespace App\Applications\Queries\Api;

use App\Models\Category;

class CategoryAllChildrenIdsQuery
{
    public function handle(string $categorySlugs): array {
        $category = Category::where('slug', $categorySlugs)->with('childrenRecursive')->first();
        $category_ids = [];
        if($category){
            $category_ids = $this->getAllChildIds($category);
            $category_ids[] = $category->id;
        }

        return $category_ids;
    }

    public function getAllChildIds($category)
    {
        $ids = [];

        foreach ($category->childrenRecursive as $child) {
            $ids[] = $child->id;

            // recursive call
            $ids = array_merge($ids, $this->getAllChildIds($child));
        }

        return $ids;
    }



}
