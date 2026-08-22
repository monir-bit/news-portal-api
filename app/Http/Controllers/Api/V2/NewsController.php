<?php

namespace App\Http\Controllers\Api\V2;

use App\Applications\Helpers\SeoHelper;
use App\Http\Controllers\Api\V2\Concerns\InteractsWithNewsRails;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryListResource;
use App\Http\Resources\Api\NewsDetailsResource;
use App\Http\Resources\Api\NewsListResource;
use App\Http\Resources\Api\NewsTimelineResource;
use App\Models\Author;
use App\Models\Category;
use App\Models\CategoryPageLayout;
use App\Models\CategoryPageLayoutNews;
use App\Models\District;
use App\Models\Division;
use App\Models\LayoutSectionNews;
use App\Models\LinkedNews;
use App\Models\News;
use App\Models\NewsTimeline;
use App\Models\PageCategoryMap;
use App\Models\Tag;
use App\Models\Upazila;
use App\Models\WorldCupMatch;
use App\Models\WorldCupQuizSet;
use App\Services\Api\NewsReadService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class NewsController extends Controller
{
    use InteractsWithNewsRails;

    public function details(string $slug, NewsReadService $newsReadService): array
    {
        $news = Cache::rememberForever(CacheKey::newsDetails($slug), function () use ($slug) {
            return News::where('slug_key', $slug)
                ->published()
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
                    'newsImages' => fn ($q) => $q->orderBy('position')->select('id', 'news_id', 'image_path', 'caption'),
                ])
                ->firstOrFail();
        });

        $newsReadService->read($news);

        return [
            'news_details' => new NewsDetailsResource($news),
            'latest_news' => $this->cachedLatestNews(),
            'most_read_news' => $this->cachedMostRead(),
            'linked_news' => $this->linkedNews($news->id),
            'news_timelines' => $this->timelines($news->id),
        ];
    }

    public function byCategoryHome(string $slug): array
    {
        return $this->categoryHomePayload($slug);
    }

    /**
     * Home page category rails in one round-trip. Reuses the same per-slug cache
     * entries as byCategoryHome.
     *
     * @return array<string, array<string, mixed>|null>
     */
    public function byCategoryHomeBatch(Request $request): array
    {
        $slugs = array_slice($this->parseSlugList($request->query('slugs')), 0, 24);

        $out = [];
        foreach ($slugs as $slug) {
            if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
                continue;
            }
            try {
                $out[$slug] = $this->categoryHomePayload($slug);
            } catch (\Throwable) {
                $out[$slug] = null;
            }
        }

        return $out;
    }

    private function categoryHomePayload(string $slug): array
    {
        return Cache::remember(CacheKey::newsByCategoryHome($slug), now()->addMinutes(5), function () use ($slug) {
            $category = Category::with('children')->where('slug', $slug)->where('visible', true)->firstOrFail();

            $excludedNewsIds = LayoutSectionNews::query()
                ->join('layout_sections', 'layout_section_news.layout_section_id', '=', 'layout_sections.id')
                ->where('layout_sections.is_enable', true)
                ->whereColumn('layout_section_news.position', '<=', 'layout_sections.max_news')
                ->pluck('layout_section_news.news_id');

            $news = News::query()
                ->whereNotIn('id', $excludedNewsIds)
                ->whereIn('category_id', $category->parent_id ? [$category->id] : [...$category->children->pluck('id'), $category->id])
                ->with('category.parentRecursive')
                ->published()
                ->orderByDesc('date')
                ->limit(15)
                ->get();

            return [
                'category' => ['name' => $category->name, 'slug' => $category->slug],
                'news' => NewsListResource::collection($news)->resolve(),
            ];
        });
    }

    public function byCategory(string $slug, Request $request): mixed
    {
        $divisionSlug = $request->input('division');
        $districtSlug = $request->input('district');
        $upazilaSlug = $request->input('upazila');
        $date = $request->input('date');

        $build = function (bool $paginatedOnly) use ($slug, $divisionSlug, $districtSlug, $upazilaSlug, $date) {
            $category = $this->visibleCategory($slug);
            $newsQuery = $this->categoryNewsQuery(Category::idsForSlug($slug));
            $this->applyGeoFilter($newsQuery, $divisionSlug, $districtSlug, $upazilaSlug);
            $this->applyDateFilter($newsQuery, $date);

            [$leadNews, $news] = $this->paginateAfterLeads($newsQuery);

            if ($paginatedOnly) {
                return NewsListResource::collection($news);
            }

            return $this->listingPayload($category, $leadNews, $news);
        };

        if ($request->input('cursor')) {
            return $build(true);
        }

        return Cache::remember(
            CacheKey::newsByCategory($slug, $divisionSlug, $districtSlug, $upazilaSlug, $date),
            now()->addMinutes(5),
            fn () => $build(false)
        );
    }

    public function byPrintCategory(string $slug, Request $request): mixed
    {
        $dateInput = $request->input('date');
        $date = $dateInput ? Carbon::parse($dateInput)->format('Y-m-d') : null;

        $build = function (bool $paginatedOnly) use ($slug, $date) {
            $category = $this->visibleCategory($slug);
            $newsQuery = $this->categoryNewsQuery(Category::idsForSlug($slug));
            $this->applyDateFilter($newsQuery, $date);

            [$leadNews, $news] = $this->paginateAfterLeads($newsQuery);

            if ($paginatedOnly) {
                return NewsListResource::collection($news);
            }

            $printEditionChildren = $date !== null ? $this->printEditionCategories($date) : null;

            return $this->listingPayload($category, $leadNews, $news, $printEditionChildren);
        };

        if ($request->input('cursor')) {
            return $build(true);
        }

        return Cache::remember(
            CacheKey::newsByPrintCategory($slug, $date),
            now()->addMinutes(5),
            fn () => $build(false)
        );
    }

    public function bySportsCategory(Request $request): array
    {
        $category = Category::with(['children', 'parent.children'])->where('slug', 'sports')->where('visible', true)->firstOrFail();
        $football = Category::where('slug', 'football')->first();
        $cricket = Category::where('slug', 'cricket')->first();

        $newsQuery = News::query()
            ->whereIn('category_id', $category->parent_id
                ? [$category->id]
                : [...$category->children->pluck('id')->reject(fn ($id) => in_array($id, [$football?->id, $cricket?->id]))->values(), $category->id])
            ->with('category.parentRecursive')
            ->published()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $news = (clone $newsQuery)->cursorPaginate(12);

        if ($request->input('cursor')) {
            return ['data' => NewsListResource::collection($news)->resolve()];
        }

        $childrenCategories = $category->parent?->children ?? $category->children;
        $parentCategory = $category->parent ?? $category;

        return [
            'category' => CategoryListResource::make($category)->resolve(),
            'parent' => CategoryListResource::make($parentCategory)->resolve(),
            'children' => CategoryListResource::collection($childrenCategories)->resolve(),
            'news_list' => [
                'lead' => $this->categoryPageLayoutNews($category->id, 'lead', 6),
                'selected' => $this->categoryPageLayoutNews($category->id, 'selected', 15),
                'cricket' => NewsListResource::collection(
                    News::whereIn('category_id', Category::idsForSlug('cricket'))->with('category.parentRecursive')->orderByDesc('date')->limit(9)->get()
                )->resolve(),
                'football' => NewsListResource::collection(
                    News::whereIn('category_id', Category::idsForSlug('football'))->with('category.parentRecursive')->orderByDesc('date')->limit(9)->get()
                )->resolve(),
                'others' => [
                    'data' => NewsListResource::collection($news->items())->resolve(),
                    'links' => ['next' => $news->nextPageUrl(), 'prev' => $news->previousPageUrl()],
                    'meta' => [
                        'next_cursor' => $news->nextCursor()?->encode(),
                        'prev_cursor' => $news->previousCursor()?->encode(),
                    ],
                ],
            ],
            'latest_news' => $this->cachedLatestNews(),
            'most_read_news_all' => $this->cachedMostRead(),
            'most_read_news' => $this->cachedMostRead($category->id, 5),
        ];
    }

    public function byWorldCupCategory(): array
    {
        $category = Category::where('slug', 'world-cup')->where('visible', true)->firstOrFail();

        $matches = WorldCupMatch::where('season', '2026')
            ->forTodayWindow()
            ->with([
                'homeTeam:id,name,flag_icon,group,fifa_code',
                'awayTeam:id,name,flag_icon,group,fifa_code',
                'commentaries' => fn ($q) => $q->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at')->limit(2),
            ])
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);

        $quizSetSlug = WorldCupQuizSet::where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->first()?->slug;

        return [
            'lead_news' => $this->categoryPageLayoutNews($category->id, 'lead-news', 10),
            'holud_jhor' => $this->categoryPageLayoutNews($category->id, 'holud-jhor', 8),
            'akashi_hawa' => $this->categoryPageLayoutNews($category->id, 'akashi-hawa', 8),
            'world_cup_analysis' => $this->categoryPageLayoutNews($category->id, 'world-cup-analysis', 1),
            'world_cup_history' => $this->categoryPageLayoutNews($category->id, 'world-cup-history', 1),
            'star_news' => $this->categoryPageLayoutNews($category->id, 'star-news', 1),
            'world_cup_thinking' => $this->categoryPageLayoutNews($category->id, 'world-cup-thinking', 1),
            'matches' => $matches,
            'quiz_set_slug' => $quizSetSlug,
        ];
    }

    public function byPrintHome(Request $request): array
    {
        $category = Category::with(['children', 'parent.children'])->where('slug', 'print')->where('visible', true)->firstOrFail();

        $selectedDate = $this->parseDateOrToday($request->query('date'));

        $pageCategories = PageCategoryMap::with([
            'category.news' => fn (Builder $q) => $q->limit(5)->whereDate('date', $selectedDate),
            'category.news.category.parentRecursive',
        ])->where('date', $selectedDate)->orderBy('position')->get()->map(fn (PageCategoryMap $item) => [
            'category' => [
                'name' => $item->category->name,
                'slug' => $item->category->slug,
                'path' => '/print/'.$item->category->slug,
            ],
            'news' => NewsListResource::collection($item->category->news)->resolve(),
        ]);

        return [
            'category' => CategoryListResource::make($category)->resolve(),
            'page_category' => $pageCategories,
            'most_read_news' => $this->cachedMostRead($category->id, 5),
        ];
    }

    public function related(News $news): Collection
    {
        $tagIds = $news->tags()->pluck('tags.id');

        $related = News::whereHas('tags', fn (Builder $q) => $q->whereIn('tag_id', $tagIds))
            ->where('id', '!=', $news->id)
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return NewsListResource::collection($related)->resolve();
    }

    public function latest(): mixed
    {
        $news = News::published()
            ->with('category.parentRecursive')
            ->orderByDesc('date')
            ->cursorPaginate(20);

        return NewsListResource::collection($news);
    }

    public function search(Request $request): mixed
    {
        $query = trim((string) $request->query('query'));

        if ($query === '') {
            return response()->json([]);
        }

        $news = News::search($query)
            ->query(fn ($builder) => $builder->where('published', true)->with('category.parentRecursive'))
            ->paginate(20);

        return NewsListResource::collection($news);
    }

    public function byTag(string $slug, Request $request): mixed
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $news = News::published()
            ->whereHas('tags', fn (Builder $q) => $q->where('slug', $slug))
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

    public function byAuthor(string $slug, Request $request): mixed
    {
        $author = Author::where('slug', $slug)->firstOrFail();

        $news = News::published()
            ->whereHas('authors', fn (Builder $q) => $q->where('authors.id', $author->id))
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->cursorPaginate(20);

        $response = NewsListResource::collection($news);

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

    // --- shared helpers (private: each used by 2+ methods above) ---

    private function visibleCategory(string $slug): Category
    {
        return Category::with(['categorySeo', 'children', 'parent.children'])
            ->where('slug', $slug)
            ->where('visible', true)
            ->firstOrFail();
    }

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function categoryNewsQuery(array $categoryIds): Builder
    {
        return News::query()
            ->whereIn('category_id', $categoryIds)
            ->with('category.parentRecursive')
            ->published()
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    private function applyDateFilter(Builder $query, ?string $date): void
    {
        if (! $date) {
            return;
        }
        $query->whereDate('date', Carbon::parse($date)->format('Y-m-d'));
    }

    private function applyGeoFilter(Builder $query, ?string $divisionSlug, ?string $districtSlug, ?string $upazilaSlug): void
    {
        if (! $divisionSlug && ! $districtSlug && ! $upazilaSlug) {
            return;
        }

        $query->whereHas('newsLocations', function (Builder $q) use ($divisionSlug, $districtSlug, $upazilaSlug) {
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
                $q->where('upazila_id', Upazila::where('slug', $upazilaSlug)->where('district_id', $districtId)->value('id'));
            }
        });
    }

    /**
     * @return array{0: Collection, 1: CursorPaginator}
     */
    private function paginateAfterLeads(Builder $newsQuery): array
    {
        $leadNews = (clone $newsQuery)->limit(3)->get();
        $paginator = (clone $newsQuery)->whereNotIn('id', $leadNews->pluck('id'))->cursorPaginate(12);

        return [$leadNews, $paginator];
    }

    private function printEditionCategories(string $date): Collection
    {
        return PageCategoryMap::query()
            ->whereDate('date', Carbon::parse($date)->format('Y-m-d'))
            ->whereHas('category', fn (Builder $q) => $q->where('visible', true))
            ->with('category.parentRecursive')
            ->orderBy('position')
            ->get()
            ->map(fn (PageCategoryMap $map) => $map->category)
            ->values();
    }

    private function listingPayload(
        Category $category,
        iterable $leadNews,
        CursorPaginator $news,
        ?Collection $childrenOverride = null,
    ): array {
        $childrenCategories = $childrenOverride
            ?? (count($category->children) > 0 ? $category->children : ($category->parent?->children ?? collect()));
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
                        'next_cursor' => $news->nextCursor()?->encode(),
                        'prev_cursor' => $news->previousCursor()?->encode(),
                    ],
                ],
            ],
            'most_read_news' => $this->cachedMostRead($category->id),
        ];
    }

    private function linkedNews(int $newsId): array
    {
        $news = LinkedNews::where('main_news_id', $newsId)
            ->whereHas('linkedArticle', fn (Builder $q) => $q->published())
            ->with('linkedArticle.category.parentRecursive')
            ->orderByDesc('created_at')
            ->get()
            ->pluck('linkedArticle');

        return NewsListResource::collection($news)->resolve();
    }

    private function timelines(int $newsId): array
    {
        $timelines = NewsTimeline::where('news_id', $newsId)
            ->where('is_publish', true)
            ->orderByDesc('created_at')
            ->select('title', 'details', 'image_path', 'image_caption', 'date')
            ->get();

        return NewsTimelineResource::collection($timelines)->resolve();
    }

    /**
     * News for a category-page layout slot (e.g. 'lead', 'selected'), in curated position order.
     *
     * @return array<int, array{position: int, news: NewsListResource}>
     */
    private function categoryPageLayoutNews(int $categoryId, string $layoutSlug, ?int $limit = null): array
    {
        $layout = CategoryPageLayout::where('category_id', $categoryId)
            ->where('slug', $layoutSlug)
            ->where('is_enable', true)
            ->first();

        if (! $layout) {
            return [];
        }

        return CategoryPageLayoutNews::with('news.category.parentRecursive')
            ->where('category_page_layout_id', $layout->id)
            ->whereHas('news', fn (Builder $q) => $q->published())
            ->orderBy('position')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get()
            ->map(fn ($item) => ['position' => $item->position, 'news' => NewsListResource::make($item->news)])
            ->values()
            ->all();
    }

    private function parseDateOrToday(?string $date): string
    {
        try {
            return $date ? Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d') : now()->format('Y-m-d');
        } catch (\Throwable) {
            return now()->format('Y-m-d');
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseSlugList(mixed $raw): array
    {
        if (is_string($raw)) {
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        if (is_array($raw)) {
            $slugs = [];
            foreach ($raw as $item) {
                if (is_string($item) && $item !== '') {
                    $slugs[] = trim($item);
                }
            }

            return array_values(array_unique($slugs));
        }

        return [];
    }
}
