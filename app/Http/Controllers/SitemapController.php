<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function __construct(
        private readonly SitemapService $sitemapService,
    ) {}

    /**
     * Paginated posts for sitemap (sitemap-post-1.xml, sitemap-post-2.xml, ...).
     */
    public function posts(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50000, max(1, (int) $request->query('per_page', 50000)));

        $posts = $this->sitemapService->getPosts($page, $perPage);
        $total = $this->sitemapService->getPostsTotalCount();
        $totalPages = $this->sitemapService->getPostsPageCount($perPage);

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
        $tags = $this->sitemapService->getTags();

        return response()->json([
            'data' => $tags,
        ]);
    }

    /**
     * All categories + static pages for sitemap-category.xml.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->sitemapService->getCategories();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * News from last 48 hours for sitemap-news.xml (Google News).
     */
    public function newsLast48Hours(): JsonResponse
    {
        $news = $this->sitemapService->getNewsLast48Hours();

        return response()->json([
            'data' => $news,
        ]);
    }

    /**
     * Sitemap index metadata (total post pages, etc.).
     */
    public function index(): JsonResponse
    {
        $postPageCount = $this->sitemapService->getPostsPageCount();

        return response()->json([
            'sitemaps' => [
                'post_pages' => $postPageCount,
                'categories' => true,
                'tags' => true,
                'news_48h' => true,
            ],
        ]);
    }
}
