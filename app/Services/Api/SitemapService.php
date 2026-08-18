<?php

namespace App\Services\Api;

use App\Models\Category;
use App\Models\News;
use App\Models\Tag;
use App\Services\CategoryPathService;

class SitemapService
{
    private const POSTS_PER_PAGE = 50000;

    public function __construct(
        private readonly CategoryPathService $categoryPathService,
    ) {}

    /**
     * Get paginated posts for sitemap (slug, url, lastmod).
     */
    public function getPosts(int $page = 1, int $perPage = self::POSTS_PER_PAGE): array
    {
        $perPage = min(self::POSTS_PER_PAGE, max(1, $perPage));
        $offset = ($page - 1) * $perPage;

        $news = News::query()
            ->where('published', true)
            ->with('category.parentRecursive')
            ->orderBy('id')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return $news->map(fn (News $item) => [
            'slug' => $item->slug_key,
            'url' => $this->buildNewsUrl($item),
            'lastmod' => $item->updated_at?->toIso8601String() ?? $item->created_at->toIso8601String(),
        ])->values()->all();
    }

    /**
     * Get total post count for pagination.
     */
    public function getPostsTotalCount(): int
    {
        return News::query()->where('published', true)->count();
    }

    /**
     * Get total number of post sitemap pages (50k per page).
     */
    public function getPostsPageCount(int $perPage = self::POSTS_PER_PAGE): int
    {
        $perPage = min(self::POSTS_PER_PAGE, max(1, $perPage));
        return (int) ceil($this->getPostsTotalCount() / $perPage);
    }

    /**
     * Get all unique tag names for sitemap.
     */
    public function getTags(): array
    {
        return Tag::query()
            ->whereHas('news', fn ($q) => $q->where('published', true))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get all category URLs for sitemap (including video, photo, static pages).
     */
    public function getCategories(): array
    {
        $categories = Category::query()
            ->where('visible', true)
            ->with('parentRecursive')
            ->get();

        $urls = collect();

        foreach ($categories as $category) {
            $path = $this->categoryPathService->build($category);
            $urls->push([
                'slug' => $category->slug,
                'url' => '/' . $path,
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

        return $urls->concat($staticPages)->unique('url')->values()->all();
    }

    /**
     * Get news from last 48 hours for Google News sitemap.
     */
    public function getNewsLast48Hours(): array
    {
        $cutoff = now()->subHours(48);

        $news = News::query()
            ->where('published', true)
            ->where('created_at', '>=', $cutoff)
            ->with('category.parentRecursive')
            ->orderByDesc('created_at')
            ->get();

        return $news->map(fn (News $item) => [
            'slug' => $item->slug_key,
            'url' => $this->buildNewsUrl($item),
            'title' => $item->title,
            'date' => $item->created_at->toIso8601String(),
        ])->values()->all();
    }

    private function buildNewsUrl(News $news): string
    {
        $category = $news->category;
        if (!$category) {
            return '/' . $news->slug_key;
        }

        $path = $this->categoryPathService->build($category);
        return '/' . $path . '/' . $news->slug_key;
    }
}
