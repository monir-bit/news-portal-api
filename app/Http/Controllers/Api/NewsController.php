<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\SeoHelper;
use App\Applications\Queries\Api\CategoryAllChildrenIdsQuery;
use App\Applications\Queries\Api\CategoryLayoutWiseNewsQuery;
use App\Applications\Queries\Api\CategoryPageLayoutWiseNewsQuery;
use App\Applications\Queries\Api\LatestNewsQuery;
use App\Applications\Queries\Api\LinkedNewsQuery;
use App\Applications\Queries\Api\MostReadNewsByCategoryQuery;
use App\Applications\Queries\Api\MostReadNewsQuery;
use App\Applications\Queries\Api\NewsTimelinesQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryListResource;
use App\Http\Resources\Api\NewsDetailsResource;
use App\Http\Resources\Api\NewsListResource;
use App\Models\Author;
use App\Models\Category;
use App\Models\LayoutSectionNews;
use App\Models\News;
use App\Models\NewsTagMapping;
use App\Models\PageCategoryMap;
use App\Models\Tag;
use App\Models\WorldCupMatch;
use App\Models\WorldCupQuizSet;
use App\Services\Api\CategoryNewsPageService;
use App\Services\Api\NewsReadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class NewsController extends Controller
{
    public function newsDetails(
        string $slug,
        MostReadNewsQuery $mostReadNewsQuery,
        LatestNewsQuery $latestNewsQuery,
        NewsReadService $newsReadService,
        LinkedNewsQuery $linkedNewsQuery,
        NewsTimelinesQuery $newsTimelinesQuery,
    ) {
        $news = Cache::rememberForever(CacheKey::newsDetails($slug), function () use ($slug) {
            return News::where('slug_key', $slug)
                ->where('published', true)
                ->with([
                    'newsSeo',
                    'category.parentRecursive',
                    'details',
                    'tags',
                    'authors:id,name,english_name,slug,designation,image',
                    'reporterNews.reporter:id,name,designation,alternate_designation',
                    'newsLocations.division:id,name',
                    'newsLocations.district:id,name',
                    'newsLocations.upazila:id,name',
                    'liveNews',
                    'newsImages' => function ($query) {
                        $query->orderBy('position')->select('id', 'news_id', 'image_path', 'caption');
                    },
                ])->firstOrFail();
        });

        $newsReadService->read($news);

        return [
            'news_details' => new NewsDetailsResource($news),
            'latest_news' => $latestNewsQuery->handle(),
            'most_read_news' => $mostReadNewsQuery->handle(),
            'linked_news' => $linkedNewsQuery->handle($news->id),
            'news_timelines' => $newsTimelinesQuery->handle($news->id),
        ];
    }

    public function newsByCategoryHome($slug, MostReadNewsByCategoryQuery $mostReadNewsQuery, Request $request)
    {
        return $this->cachedNewsByCategoryHome($slug);
    }

    /**
     * Home page category rails in one round-trip. Reuses the same per-slug cache keys as newsByCategoryHome.
     *
     * @return array<string, array<string, mixed>|null>
     */
    public function newsByCategoryHomeBatch(Request $request): array
    {
        $raw = $request->query('slugs');
        if (is_string($raw)) {
            $slugs = array_values(array_filter(array_map('trim', explode(',', $raw))));
        } elseif (is_array($raw)) {
            $slugs = [];
            foreach ($raw as $item) {
                if (is_string($item) && $item !== '') {
                    $slugs[] = trim($item);
                }
            }
            $slugs = array_values(array_unique($slugs));
        } else {
            $slugs = [];
        }

        $slugs = array_slice($slugs, 0, 24);

        $out = [];
        foreach ($slugs as $slug) {
            if (! is_string($slug) || ! preg_match('/^[a-z0-9\-]+$/', $slug)) {
                continue;
            }
            try {
                $out[$slug] = $this->cachedNewsByCategoryHome($slug);
            } catch (\Throwable) {
                $out[$slug] = null;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected function cachedNewsByCategoryHome(string $slug): array
    {
        return Cache::remember(CacheKey::newsByCategoryHome($slug), now()->addMinutes(5), function () use ($slug) {
            $category = Category::with('children')->where('slug', $slug)->where('visible', true)->firstOrFail();

            $layoutSectionNewsIds = LayoutSectionNews::query()
                ->join('layout_sections', 'layout_section_news.layout_section_id', '=', 'layout_sections.id')
                ->where('layout_sections.is_enable', true)
                ->whereColumn('layout_section_news.position', '<=', 'layout_sections.max_news')
                ->pluck('layout_section_news.news_id')
                ->toArray();

            $newsQuery = News::query()
                ->whereNotIn('id', $layoutSectionNewsIds)
                ->when($category->parent_id, function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })
                ->when(! $category->parent_id, function ($query) use ($category) {
                    $children_category_ids = $category->children->pluck('id');
                    $children_category_ids[] = $category->id;
                    $query->whereIn('category_id', $children_category_ids);
                })
                ->with('category.parentRecursive')
                ->where('published', true)
                ->orderByDesc('date');

            $news = (clone $newsQuery)->limit(15)->get();

            return [
                'category' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'news' => NewsListResource::collection($news)->resolve(),
            ];
        });
    }

    public function newsByCategory(
        string $slug,
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        Request $request,
        CategoryNewsPageService $categoryNewsPageService,
    ) {
        $divisionSlug = $request->input('division');
        $districtSlug = $request->input('district');
        $upazilaSlug = $request->input('upazila');
        $date = $request->input('date');

        $load = function (bool $onlyPaginated) use (
            $slug,
            $mostReadNewsQuery,
            $categoryNewsPageService,
            $divisionSlug,
            $districtSlug,
            $upazilaSlug,
            $date,
        ) {
            $category = $categoryNewsPageService->resolveVisibleCategory($slug);
            $categoryIds = $categoryNewsPageService->categoryIdsForSlug($slug);
            $newsQuery = $categoryNewsPageService->baseNewsQuery($categoryIds);
            $categoryNewsPageService->applyGeoFilter($newsQuery, $divisionSlug, $districtSlug, $upazilaSlug);
            $categoryNewsPageService->applyDateFilter($newsQuery, $date);

            [$leadNews, $news] = $categoryNewsPageService->paginateAfterLeads($newsQuery);

            if ($onlyPaginated) {
                return NewsListResource::collection($news);
            }

            return $categoryNewsPageService->buildFullListingPayload(
                $category,
                $leadNews,
                $news,
                $mostReadNewsQuery,
            );
        };

        if ($request->input('cursor')) {
            return $load(true);
        }

        return Cache::remember(
            CacheKey::newsByCategory($slug, $divisionSlug, $districtSlug, $upazilaSlug, $date),
            now()->addMinutes(5),
            fn () => $load(false)
        );
    }

    public function newsByPrintCategory(
        string $slug,
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        Request $request,
        CategoryNewsPageService $categoryNewsPageService,
    ) {
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : null;

        $load = function (bool $onlyPaginated) use ($slug, $mostReadNewsQuery, $categoryNewsPageService, $date) {
            $category = $categoryNewsPageService->resolveVisibleCategory($slug);
            $categoryIds = $categoryNewsPageService->categoryIdsForSlug($slug);
            $newsQuery = $categoryNewsPageService->baseNewsQuery($categoryIds);
            $categoryNewsPageService->applyDateFilter($newsQuery, $date);

            [$leadNews, $news] = $categoryNewsPageService->paginateAfterLeads($newsQuery);

            if ($onlyPaginated) {
                return NewsListResource::collection($news);
            }

            $printEditionChildren = $date !== null
                ? $categoryNewsPageService->categoriesOrderedForPrintEdition($date)
                : null;

            return $categoryNewsPageService->buildFullListingPayload(
                $category,
                $leadNews,
                $news,
                $mostReadNewsQuery,
                $printEditionChildren,
            );
        };

        if ($request->input('cursor')) {
            return $load(true);
        }

        return Cache::remember(
            CacheKey::newsByPrintCategory($slug, $date),
            now()->addMinutes(5),
            fn () => $load(false)
        );
    }

    public function newsByCategorySports(
        CategoryPageLayoutWiseNewsQuery $categoryPageLayoutWiseNewsQuery,
        CategoryLayoutWiseNewsQuery $categoryLayoutWiseNewsQuery,
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        MostReadNewsQuery $mostReadNewsAllQuery,
        LatestNewsQuery $latestNewsQuery,
        CategoryAllChildrenIdsQuery $categoryAllChildrenIdsQuery,
        Request $request
    ) {

        $slug = 'sports';

        $category = Category::with(['children', 'parent.children'])
            ->where('slug', $slug)
            ->where('visible', true)
            ->firstOrFail();

        $footballCategory = Category::where('slug', 'football')->first();
        $cricketCategory = Category::where('slug', 'cricket')->first();
        $cricketCategoryNews = News::with('category.parentRecursive')->whereIn('category_id', $categoryAllChildrenIdsQuery->handle($cricketCategory->slug))->limit(9)->orderByDesc('date')->get();
        $footballCategoryNews = News::with('category.parentRecursive')->whereIn('category_id', $categoryAllChildrenIdsQuery->handle($footballCategory->slug))->limit(9)->orderByDesc('date')->get();
        $newsQuery = News::query()
            ->when($category->parent_id, function ($query) use ($category) {
                $query->where('category_id', $category->id);
            })
            ->when(! $category->parent_id, function ($query) use ($category, $footballCategory, $cricketCategory) {
                $children_category_ids = $category->children
                    ->pluck('id')
                    ->reject(fn ($id) => in_array($id, [
                        $footballCategory?->id,
                        $cricketCategory?->id,
                    ]))
                    ->values();

                $children_category_ids[] = $category->id;

                $query->whereIn('category_id', $children_category_ids);
            })
            ->with('category.parentRecursive')
            ->where('published', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id'); // cursor pagination stable

        // Cursor pagination (no offset)
        $news = (clone $newsQuery)->cursorPaginate(12);

        // Infinite scroll request
        if ($request->input('cursor')) {
            return NewsListResource::collection($news);
        }

        $childrenCategories = $category->parent ? $category->parent->children : $category->children;
        $parentCategory = $category->parent ?? $category;

        return [
            'category' => CategoryListResource::make($category)->resolve(),

            'parent' => CategoryListResource::make($parentCategory)->resolve(),

            'children' => CategoryListResource::collection($childrenCategories)->resolve(),

            'news_list' => [

                'lead' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'lead', 6),

                'selected' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'selected', 15),

                'cricket' => NewsListResource::collection($cricketCategoryNews)->resolve(),
                //                'cricket' => $categoryLayoutWiseNewsQuery->handle(
                //                    'category-lead-news',
                //                    $cricketCategory->id,
                //                    9
                //                ),

                'football' => NewsListResource::collection($footballCategoryNews)->resolve(),

                'others' => [
                    'data' => NewsListResource::collection($news->items()),

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

            'latest_news' => $latestNewsQuery->handle(),

            'most_read_news_all' => $mostReadNewsAllQuery->handle(),

            'most_read_news' => $mostReadNewsQuery->handle($category->id, 5),
        ];
    }

    public function newsByCategoryWorldCup(
        CategoryPageLayoutWiseNewsQuery $categoryPageLayoutWiseNewsQuery,
        Request $request
    ) {

        $slug = 'world-cup';
        $category = Category::with(['children', 'parent.children'])
            ->where('slug', $slug)
            ->where('visible', true)
            ->firstOrFail();

        $matches = WorldCupMatch::where('season', '2026')
            ->forTodayWindow()
            ->with(['homeTeam:id,name,flag_icon,group,fifa_code', 'awayTeam:id,name,flag_icon,group,fifa_code', 'commentaries' => function ($query) {
                $query->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at')->limit(2);
            }])
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);

        $quiz_set_slug = WorldCupQuizSet::where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->first()?->slug;

        return [
            'lead_news' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'lead-news', 10),
            'holud_jhor' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'holud-jhor', 8),
            'akashi_hawa' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'akashi-hawa', 8),
            'world_cup_analysis' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'world-cup-analysis', 1),
            'world_cup_history' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'world-cup-history', 1),
            'star_news' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'star-news', 1),
            'world_cup_thinking' => $categoryPageLayoutWiseNewsQuery->handle($category->id, 'world-cup-thinking', 1),
            'matches' => $matches,
            'quiz_set_slug' => $quiz_set_slug,
        ];
    }

    public function newsByCategoryPrint(
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        Request $request
    ) {

        $slug = 'print';

        $category = Category::with(['children', 'parent.children'])
            ->where('slug', $slug)
            ->where('visible', true)
            ->firstOrFail();

        $selectedDateInput = $request->query('date');
        try {
            $selectedDate = $selectedDateInput
                ? Carbon::createFromFormat('Y-m-d', $selectedDateInput)->format('Y-m-d')
                : now()->format('Y-m-d');
        } catch (\Throwable) {
            $selectedDate = now()->format('Y-m-d');
        }

        $page_category = PageCategoryMap::with([
            'category.news' => function ($query) use ($selectedDate) {
                $query->limit(5)->whereDate('date', $selectedDate);
            },
            'category.news.category.parentRecursive',
        ])->where('date', $selectedDate)->orderBy('position')->get()->map(function ($item) {

            $category = $item->category;
            $news = $category->news;

            return [
                'category' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'path' => '/print/'.$category->slug,
                ],
                'news' => NewsListResource::collection($news)->resolve(),
            ];
        });

        return [
            'category' => CategoryListResource::make($category)->resolve(),
            'page_category' => $page_category,
            'most_read_news' => $mostReadNewsQuery->handle($category->id, 5),
        ];

    }

    public function relatedNews(News $news)
    {
        $tag_ids = NewsTagMapping::where('news_id', $news->id)->pluck('tag_id');

        $related_news = News::whereHas('tags', function ($query) use ($tag_ids) {
            $query->whereIn('tag_id', $tag_ids);
        })
            ->where('id', '!=', $news->id)
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $related_news;
    }

    public function latestNews()
    {
        $news = News::where('published', true)
            ->with('category.parentRecursive')
            ->orderByDesc('date')
            ->cursorPaginate(20);

        return NewsListResource::collection($news);
    }

    public function searchNews(Request $request)
    {
        $query = trim($request->query('query'));

        if (! $query) {
            return response()->json([]);
        }

        $news = News::search($query)
            ->query(function ($builder) {
                $builder
                    ->where('published', true)
                    ->with('category.parentRecursive');
            })
            ->paginate(20);

        return NewsListResource::collection($news);
    }

    public function newsByTags($name, Request $request)
    {
        $tag = Tag::where('slug', $name)->firstOrFail();
        $news = News::where('published', true)
            ->whereHas('tags', function ($q) use ($name) {
                $q->where('slug', $name);
            })
            ->with('category.parentRecursive')
            ->latest()
            ->cursorPaginate(20);

        $response = NewsListResource::collection($news);

        if ($request->has('cursor')) {
            return $response;
        }

        return response()->json([
            'seo_meta' => SeoHelper::Make(
                title: $tag?->title ?? '',
                image: $tag?->og_image ?? '',
                description: $tag?->description ?? '',
                keywords: $tag?->keywords ?? [],
            ),
            'news_list' => $response->response()->getData(true),
        ]);
    }

    public function newsByAuthor(string $slug, Request $request)
    {
        $author = Author::where('slug', $slug)->firstOrFail();

        $newsQuery = News::query()
            ->where('published', true)
            ->whereHas('authors', function ($q) use ($author) {
                $q->where('authors.id', $author->id);
            })
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->cursorPaginate(20);

        $response = NewsListResource::collection($newsQuery);

        if ($request->has('cursor')) {
            return $response;
        }

        return [
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'english_name' => $author->english_name,
                'designation' => $author->designation,
                'bio' => $author->bio,
                'image' => $author->image,
                'facebook' => $author->facebook,
                'email' => $author->email,
                'linkedin_url' => $author->linkedin_url,
            ],
            ...$response->response()->getData(true),
        ];
    }
}
