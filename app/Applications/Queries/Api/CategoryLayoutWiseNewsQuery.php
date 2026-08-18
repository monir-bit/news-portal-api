<?php

namespace App\Applications\Queries\Api;

use App\Http\Resources\Api\NewsListResource;
use App\Models\CategoryLayout;

class CategoryLayoutWiseNewsQuery
{
    public function handle(string $section_slug, int $category_id, $limit = null){
        $layout_section = CategoryLayout::with([
            'layoutNews' => function ($q) use ( $category_id, $limit) {
                $q->with([
                    'news' => function ($q) {
                        $q->whereNull('deleted_at');
                        $q->where('published', true);
                    },
                    'news.category.parentRecursive'
                ])->where('category_id', $category_id)->orderBy('position', 'ASC')->limit($limit);
            },
        ])->where('slug', $section_slug)->first();
        if(!$layout_section) {
            return [];
        }




        $news_list = $layout_section->layoutNews->map(function ($item) {
                return [
                    'position' => $item->position,
                    'news' => NewsListResource::make($item->news),
                ];
            });


        return $news_list;

    }
}
