<?php

namespace App\Applications\Queries\Api;

use App\Models\Category;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class RecursiveCategoryQuery
{
    public function handle()
    {
        return app(SharedCache::class)->remember(CacheKey::category(), [CacheTags::categoryTree()], function () {
            $printCategories = app(CategoryAllChildrenIdsQuery::class)->handle('print');

            return Category::query()
                ->whereNotIn('id', $printCategories)
                ->whereNull('parent_id')
                ->where('visible', true)
                ->orderBy('position')
                ->with([
                    'childrenRecursive', // 👈 enough (parent already included inside)
                ])
                ->select('id', 'parent_id', 'name', 'slug')
                ->get();
        }, 86400);
    }
}
