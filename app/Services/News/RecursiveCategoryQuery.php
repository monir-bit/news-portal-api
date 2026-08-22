<?php

namespace App\Services\News;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;

class RecursiveCategoryQuery
{
    public function __construct(
        private readonly SharedCache $sharedCache,
    ) {}

    /**
     * @return Collection<int, Category>
     */
    public function handle(): Collection
    {
        return $this->sharedCache->remember(
            CacheKey::category(),
            [CacheTags::categoryTree()],
            fn () => $this->build(),
            86400,
        );
    }

    /**
     * @return Collection<int, Category>
     */
    private function build(): Collection
    {
        $printCategoryIds = app(CategoryAllChildrenIdsQuery::class)->handle('print');

        return Category::query()
            ->whereNotIn('id', $printCategoryIds)
            ->whereNull('parent_id')
            ->where('visible', true)
            ->orderBy('position')
            ->with(['childrenRecursive'])
            ->select('id', 'parent_id', 'name', 'slug')
            ->get();
    }
}
