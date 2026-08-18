<?php

namespace App\Applications\Queries\Api;

use App\Applications\Cache\CacheKey;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class RecursiveCategoryQuery
{
    public function handle()
    {
        return Cache::remember(CacheKey::category(), now()->addDay(), function () {
            $printCategories = app(CategoryAllChildrenIdsQuery::class)->handle('print');
            return Category::query()
                ->whereNotIn('id', $printCategories)
                ->whereNull('parent_id')
                ->where('visible', true)
                ->orderBy('position')
                ->with([
                    'childrenRecursive' // 👈 enough (parent already included inside)
                ])
                ->select('id', 'parent_id', 'name', 'slug')
                ->get();
        });
    }
}
