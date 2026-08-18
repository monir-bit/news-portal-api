<?php

namespace App\Services\Api;

use App\Applications\Helpers\SeoHelper;
use App\Applications\Queries\Api\CategoryAllChildrenIdsQuery;
use App\Applications\Queries\Api\MostReadNewsByCategoryQuery;
use App\Http\Resources\Api\CategoryListResource;
use App\Http\Resources\Api\NewsListResource;
use App\Models\Category;
use App\Models\District;
use App\Models\Division;
use App\Models\News;
use App\Models\PageCategoryMap;
use App\Models\Upazila;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryNewsPageService
{
    public function __construct(
        private CategoryAllChildrenIdsQuery $categoryAllChildrenIdsQuery,
    ) {}

    public function categoryIdsForSlug(string $slug): array
    {
        return $this->categoryAllChildrenIdsQuery->handle($slug);
    }

    public function resolveVisibleCategory(string $slug): Category
    {
        return Category::with(['categorySeo', 'children', 'parent.children'])
            ->where('slug', $slug)
            ->where('visible', true)
            ->firstOrFail();
    }

    public function baseNewsQuery(array $categoryIds): Builder
    {
        return News::query()
            ->whereIn('category_id', $categoryIds)
            ->with('category.parentRecursive')
            ->where('published', true)
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    public function applyDateFilter(Builder $query, ?string $date): void
    {
        if ($date === null || $date === '') {
            return;
        }
        $parsedDate = Carbon::parse($date)->format('Y-m-d');
        $query->whereDate('date', $parsedDate);
    }

    public function applyGeoFilter(
        Builder $query,
        ?string $divisionSlug,
        ?string $districtSlug,
        ?string $upazilaSlug,
    ): void {
        if (!$divisionSlug && !$districtSlug && !$upazilaSlug) {
            return;
        }

        $query->whereHas('newsLocations', function ($q) use ($divisionSlug, $districtSlug, $upazilaSlug) {
            $divisionId = null;
            $districtId = null;

            if ($divisionSlug) {
                $divisionId = Division::where('slug', $divisionSlug)->value('id');
                $q->where('division_id', $divisionId);
            }
            if ($districtSlug && $divisionId) {
                $districtId = District::where('slug', $districtSlug)->where('division_id', $divisionId)->value('id');
                $q->where('district_id', $districtId);
            }
            if ($upazilaSlug && $districtId) {
                $upazilaId = Upazila::where('slug', $upazilaSlug)->where('district_id', $districtId)->value('id');
                $q->where('upazila_id', $upazilaId);
            }
        });
    }

    public function paginateAfterLeads(Builder $newsQuery): array
    {
        $leadNews = (clone $newsQuery)->take(3)->get();
        $leadIds = $leadNews->pluck('id');
        $paginator = (clone $newsQuery)
            ->whereNotIn('id', $leadIds)
            ->cursorPaginate(12);

        return [$leadNews, $paginator];
    }

    /**
     * Print horizontal nav: categories mapped for this edition date (`page_category_maps.position`).
     */
    public function categoriesOrderedForPrintEdition(string $date): Collection
    {
        $parsedDate = Carbon::parse($date)->format('Y-m-d');

        return PageCategoryMap::query()
            ->whereDate('date', $parsedDate)
            ->whereHas('category', fn ($q) => $q->where('visible', true))
            ->with(['category.parentRecursive'])
            ->orderBy('position')
            ->get()
            ->map(fn (PageCategoryMap $map) => $map->category)
            ->values();
    }

    public function buildFullListingPayload(
        Category $category,
        iterable $leadNews,
        CursorPaginator $news,
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        ?Collection $printEditionChildren = null,
    ): array {
        if ($printEditionChildren !== null) {
            $childrenCategories = $printEditionChildren;
        } else {
            $childrenCategories = count($category->children) > 0
                ? $category->children
                : ($category->parent ? $category->parent->children : collect());
        }
        $parentCategory = $category->parent ?? $category;

        return [
            'category' => CategoryListResource::make($category)->resolve(),
            'seo_meta' => SeoHelper::Make(
                title: $category->categorySeo?->title ?? $category->name,
                image: $category->categorySeo?->og_image ?? '',
                description: $category->categorySeo?->description ?? '',
                keywords: $category->categorySeo?->keywords ?? [],
            ),
            'parent' => CategoryListResource::make($parentCategory)->resolve(),
            'children' => CategoryListResource::collection($childrenCategories)->resolve(),
            'news_list' => [
                'lead_news' => NewsListResource::collection($leadNews)->resolve(),
                'news' => [
                    'data' => NewsListResource::collection($news->items())->resolve(),
                    'links' => [
                        'next' => $news->nextPageUrl(),
                        'prev' => $news->previousPageUrl(),
                    ],
                    'meta' => [
                        'next_cursor' => optional($news->nextCursor())->encode(),
                        'prev_cursor' => optional($news->previousCursor())->encode(),
                    ],
                ],
            ],
            'most_read_news' => $mostReadNewsQuery->handle($category->id)->resolve(),
        ];
    }
}
