<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\CategoryListResource;
use App\Http\Resources\Api\NewsListResource;
use App\Models\Category;
use App\Models\News;

class CategoryLatestNewsQuery
{
    public function handle($slug, $offset= 0, $limit = 15) {
        $category = Category::where('slug', $slug)->first();
        $news = News::where('published', true)
            ->where('category_id', $category->id)
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'category' => CategoryListResource::make($category)->resolve(),
            'news' => NewsListResource::collection($news)->resolve(),
        ];
    }

}
