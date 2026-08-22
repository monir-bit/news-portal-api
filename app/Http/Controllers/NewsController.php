<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsDetailsResource;
use App\Http\Resources\NewsListResource;
use App\Models\Author;
use App\Models\Category;
use App\Models\LayoutSectionNews;
use App\Models\News;
use App\Models\Tag;
use App\Services\News\CategoryNewsPageService;
use App\Services\News\LatestNewsQuery;
use App\Services\News\LinkedNewsQuery;
use App\Services\News\MostReadNewsByCategoryQuery;
use App\Services\News\MostReadNewsQuery;
use App\Services\News\NewsReadService;
use App\Services\News\NewsTimelinesQuery;
use App\Support\SeoHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use Rakibmiah99\AgamirsomoySharedCache\CacheTags;
use Rakibmiah99\AgamirsomoySharedCache\SharedCache;
use Throwable;

class NewsController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function newsDetails(
        string $slug,
        MostReadNewsQuery $mostReadNewsQuery,
        LatestNewsQuery $latestNewsQuery,
        NewsReadService $newsReadService,
        LinkedNewsQuery $linkedNewsQuery,
        NewsTimelinesQuery $newsTimelinesQuery,
        SharedCache $sharedCache,
    ): array {
        $news = $sharedCache->rememberLong(
            CacheKey::newsDetails($slug),
            [CacheTags::newsBySlug($slug)],
            fn () => News::where('slug_key', $slug)
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
                ])->firstOrFail(),
            21600,
        );

        $newsReadService->read($news);

        return [
            'news_details' => new NewsDetailsResource($news),
            'latest_news' => $latestNewsQuery->handle(),
            'most_read_news' => $mostReadNewsQuery->handle(),
            'linked_news' => $linkedNewsQuery->handle($news->id),
            'news_timelines' => $newsTimelinesQuery->handle($news->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function newsByCategoryHome(string $slug, SharedCache $sharedCache): array
    {
        return $this->buildNewsByCategoryHome($slug, $sharedCache);
    }

    /**
     * Home page category rails in one round-trip.
     *
     * @return array<string, array<string, mixed>|null>
     */
    public function newsByCategoryHomeBatch(Request $request, SharedCache $sharedCache): array
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
                $out[$slug] = $this->buildNewsByCategoryHome($slug, $sharedCache);
            } catch (Throwable) {
                $out[$slug] = null;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNewsByCategoryHome(string $slug, SharedCache $sharedCache): array
    {
        return $sharedCache->remember(
            CacheKey::newsByCategoryHome($slug),
            [CacheTags::categoryBySlug($slug)],
            function () use ($slug) {
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
                        $childrenCategoryIds = $category->children->pluck('id');
                        $childrenCategoryIds[] = $category->id;
                        $query->whereIn('category_id', $childrenCategoryIds);
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
            },
            300,
        );
    }

    /**
     * @return array<string, mixed>|AnonymousResourceCollection
     */
    public function newsByCategory(
        string $slug,
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        Request $request,
        CategoryNewsPageService $categoryNewsPageService,
        SharedCache $sharedCache,
    ): array|AnonymousResourceCollection {
        $divisionSlug = $request->input('division');
        $districtSlug = $request->input('district');
        $upazilaSlug = $request->input('upazila');
        $date = $request->input('date');

        // Cursor pages are unique per request (unbounded key space) - only the
        // default/first-page view (no cursor) is cache-eligible.
        if ($request->input('cursor')) {
            $categoryIds = $categoryNewsPageService->categoryIdsForSlug($slug);
            $newsQuery = $categoryNewsPageService->baseNewsQuery($categoryIds);
            $categoryNewsPageService->applyGeoFilter($newsQuery, $divisionSlug, $districtSlug, $upazilaSlug);
            $categoryNewsPageService->applyDateFilter($newsQuery, $date);

            [, $news] = $categoryNewsPageService->paginateAfterLeads($newsQuery);

            return NewsListResource::collection($news);
        }

        return $sharedCache->remember(
            CacheKey::newsByCategory($slug, $divisionSlug, $districtSlug, $upazilaSlug, $date),
            [CacheTags::categoryBySlug($slug)],
            function () use ($slug, $divisionSlug, $districtSlug, $upazilaSlug, $date, $mostReadNewsQuery, $categoryNewsPageService) {
                $category = $categoryNewsPageService->resolveVisibleCategory($slug);
                $categoryIds = $categoryNewsPageService->categoryIdsForSlug($slug);
                $newsQuery = $categoryNewsPageService->baseNewsQuery($categoryIds);
                $categoryNewsPageService->applyGeoFilter($newsQuery, $divisionSlug, $districtSlug, $upazilaSlug);
                $categoryNewsPageService->applyDateFilter($newsQuery, $date);

                [$leadNews, $news] = $categoryNewsPageService->paginateAfterLeads($newsQuery);

                return $categoryNewsPageService->buildFullListingPayload(
                    $category,
                    $leadNews,
                    $news,
                    $mostReadNewsQuery,
                );
            },
            300,
        );
    }

    /**
     * @return array<string, mixed>|AnonymousResourceCollection
     */
    public function newsByPrintCategory(
        string $slug,
        MostReadNewsByCategoryQuery $mostReadNewsQuery,
        Request $request,
        CategoryNewsPageService $categoryNewsPageService,
        SharedCache $sharedCache,
    ): array|AnonymousResourceCollection {
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : null;

        // Cursor pages are unique per request (unbounded key space) - only the
        // default/first-page view (no cursor) is cache-eligible.
        if ($request->input('cursor')) {
            $categoryIds = $categoryNewsPageService->categoryIdsForSlug($slug);
            $newsQuery = $categoryNewsPageService->baseNewsQuery($categoryIds);
            $categoryNewsPageService->applyDateFilter($newsQuery, $date);

            [, $news] = $categoryNewsPageService->paginateAfterLeads($newsQuery);

            return NewsListResource::collection($news);
        }

        return $sharedCache->remember(
            CacheKey::newsByPrintCategory($slug, $date),
            [CacheTags::categoryBySlug($slug)],
            function () use ($slug, $date, $mostReadNewsQuery, $categoryNewsPageService) {
                $category = $categoryNewsPageService->resolveVisibleCategory($slug);
                $categoryIds = $categoryNewsPageService->categoryIdsForSlug($slug);
                $newsQuery = $categoryNewsPageService->baseNewsQuery($categoryIds);
                $categoryNewsPageService->applyDateFilter($newsQuery, $date);

                [$leadNews, $news] = $categoryNewsPageService->paginateAfterLeads($newsQuery);

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
            },
            300,
        );
    }

    public function latestNews(): AnonymousResourceCollection
    {
        $news = News::where('published', true)
            ->with('category.parentRecursive')
            ->orderByDesc('date')
            ->cursorPaginate(20);

        return NewsListResource::collection($news);
    }

    public function searchNews(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $query = trim((string) $request->query('query'));

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

    public function newsByTags(string $name, Request $request): AnonymousResourceCollection|JsonResponse
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
                title: $tag->title ?? '',
                image: $tag->og_image ?? '',
                description: $tag->description ?? '',
                keywords: $tag->keywords ?? [],
            ),
            'news_list' => $response->response()->getData(true),
        ]);
    }

    /**
     * @return array<string, mixed>|AnonymousResourceCollection
     */
    public function newsByAuthor(string $slug, Request $request, SharedCache $sharedCache): array|AnonymousResourceCollection
    {
        $author = Author::where('slug', $slug)->firstOrFail();

        // Cursor pages are unique per request (unbounded key space) - only the
        // default/first-page view (no cursor) is cache-eligible.
        if ($request->has('cursor')) {
            $newsQuery = News::query()
                ->where('published', true)
                ->whereHas('authors', function ($q) use ($author) {
                    $q->where('authors.id', $author->id);
                })
                ->with('category.parentRecursive')
                ->orderByDesc('created_at')
                ->cursorPaginate(20);

            return NewsListResource::collection($newsQuery);
        }

        return $sharedCache->remember(
            CacheKey::newsByAuthor($author->id),
            [CacheTags::author($author->id)],
            function () use ($author) {
                $newsQuery = News::query()
                    ->where('published', true)
                    ->whereHas('authors', function ($q) use ($author) {
                        $q->where('authors.id', $author->id);
                    })
                    ->with('category.parentRecursive')
                    ->orderByDesc('created_at')
                    ->cursorPaginate(20);

                $response = NewsListResource::collection($newsQuery);

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
            },
            300,
        );
    }
}
