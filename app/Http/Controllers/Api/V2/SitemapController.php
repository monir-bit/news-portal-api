<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\News;
use App\Models\Tag;
use App\Services\CategoryPathService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    private const POSTS_PER_PAGE = 50000;

    public function __construct(
        private readonly CategoryPathService $categoryPathService,
    ) {}

    /**
     * Paginated posts for sitemap (sitemap-post-1.xml, sitemap-post-2.xml, ...).
     */
    public function posts(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(self::POSTS_PER_PAGE, max(1, (int) $request->query('per_page', self::POSTS_PER_PAGE)));
        $offset = ($page - 1) * $perPage;

        $news = News::query()
            ->where('published', true)
            ->with('category.parentRecursive')
            ->orderBy('id')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $posts = $news->map(fn (News $item) => [
            'slug' => $item->slug_key,
            'url' => $this->buildNewsUrl($item),
            'lastmod' => $item->updated_at?->toIso8601String() ?? $item->created_at->toIso8601String(),
        ])->values()->all();

        $total = News::query()->where('published', true)->count();
        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'data' => $posts,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    /**
     * All tags for sitemap-tag.xml.
     */
    public function tags(): JsonResponse
    {
        $tags = Tag::query()
            ->whereHas('news', fn ($q) => $q->where('published', true))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'data' => $tags,
        ]);
    }

    /**
     * All category URLs + static pages for sitemap-category.xml.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('visible', true)
            ->with('parentRecursive')
            ->get();

        $urls = collect();

        foreach ($categories as $category) {
            $urls->push([
                'slug' => $category->slug,
                'url' => '/'.$this->categoryPathService->build($category),
            ]);
        }

        $staticPages = [
            ['slug' => 'home', 'url' => '/'],
            ['slug' => 'latest', 'url' => '/collection/latest'],
            ['slug' => 'terms', 'url' => '/terms'],
            ['slug' => 'about', 'url' => '/about'],
            ['slug' => 'contact', 'url' => '/contact'],
            ['slug' => 'privacy', 'url' => '/privacy'],
            ['slug' => 'photo', 'url' => '/photo'],
            ['slug' => 'video', 'url' => '/video'],
        ];

        return response()->json([
            'data' => $urls->concat($staticPages)->unique('url')->values()->all(),
        ]);
    }

    /**
     * News from last 48 hours for sitemap-news.xml (Google News).
     */
    public function newsLast48Hours(): JsonResponse
    {
        $cutoff = now()->subHours(48);

        $news = News::query()
            ->where('published', true)
            ->where('created_at', '>=', $cutoff)
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->get();

        $data = $news->map(fn (News $item) => [
            'slug' => $item->slug_key,
            'url' => $this->buildNewsUrl($item),
            'title' => $item->title,
            'date' => $item->created_at->toIso8601String(),
        ])->values()->all();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Sitemap index metadata (total post pages, etc.).
     */
    public function index(): JsonResponse
    {
        $postPageCount = (int) ceil(
            News::query()->where('published', true)->count() / self::POSTS_PER_PAGE
        );

        return response()->json([
            'sitemaps' => [
                'post_pages' => $postPageCount,
                'categories' => true,
                'tags' => true,
                'news_48h' => true,
            ],
        ]);
    }

    private function buildNewsUrl(News $news): string
    {
        $category = $news->category;
        if ($category === null) {
            return '/'.$news->slug_key;
        }

        return '/'.$this->categoryPathService->build($category).'/'.$news->slug_key;
    }
}
