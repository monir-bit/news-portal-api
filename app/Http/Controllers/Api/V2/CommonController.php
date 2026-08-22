<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryTreeResource;
use App\Http\Resources\Api\NewsListResource;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\LayoutSection;
use App\Models\LayoutSectionNews;
use App\Models\MarqueNews;
use App\Models\News;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Rakibmiah99\AgamirsomoySharedCache\CacheKey;

class CommonController extends Controller
{
    /**
     * Bootstrap payload fetched on every page load: "thank you" rail, site info,
     * category tree, marquee news, breaking-news ticker, and current environment.
     *
     * v1 injected an unused `HeaderNewsQuery` into this action (never called in
     * the method body) — dropped here as dead code with no behavior change.
     *
     * @return array<string, mixed>
     */
    public function common(): array
    {
        return [
            'thank_news' => $this->thankNews(),
            'site_info' => [
                'name' => 'আগামীর সময়',
                'description' => 'আগামীর সময় একটি অনলাইন নিউজ পোর্টাল...',
            ],
            'categories' => CategoryTreeResource::collection($this->categoryTree()),
            'marque_news' => $this->marqueNews(),
            'breaking_news' => $this->breakingNews(),
            'env' => App::environment(),
        ];
    }

    /**
     * Root-level visible categories (with recursive children), excluding the
     * "print" section and its descendants (print has its own navigation).
     */
    private function categoryTree(): Collection
    {
        return Cache::remember(CacheKey::category(), now()->addDay(), function () {
            $printCategoryIds = Category::idsForSlug('print');

            return Category::query()
                ->whereNotIn('id', $printCategoryIds)
                ->whereNull('parent_id')
                ->where('visible', true)
                ->orderBy('position')
                ->with('childrenRecursive')
                ->select('id', 'parent_id', 'name', 'slug')
                ->get();
        });
    }

    /**
     * @return array{meta: array<string, mixed>|null, news: array<string, mixed>}|array{}
     */
    private function thankNews(): array
    {
        // Suffixed so this never collides with the home-section-wise-news cache
        // entries for the same section slug (they share the same key prefix).
        return Cache::flexible(CacheKey::homeSectionWiseNews('thanks').':thanks-block', [300, 900], function () {
            $sectionId = LayoutSection::where('slug', 'thanks')->value('id');

            if (! $sectionId) {
                return [];
            }

            $section = LayoutSectionNews::query()
                ->select(['layout_section_news.id', 'layout_section_news.news_id', 'layout_section_news.position'])
                ->join('news', 'news.id', '=', 'layout_section_news.news_id')
                ->with([
                    'news' => fn ($q) => $q->select(NewsListResource::NEWS_COLUMNS),
                    'news.liveNews' => fn ($q) => $q->select(NewsListResource::LIVE_NEWS_COLUMNS),
                    'news.thankNews',
                    'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->where('layout_section_news.layout_section_id', $sectionId)
                ->whereNull('news.deleted_at')
                ->where('news.published', true)
                ->orderBy('layout_section_news.position')
                ->first();

            if (! $section || ! $section->news) {
                return [];
            }

            return [
                'meta' => $section->news->thankNews?->toArray(),
                'news' => NewsListResource::make($section->news)->resolve(),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function marqueNews(): array
    {
        return Cache::remember(CacheKey::marque(), now()->addMinutes(5), function () {
            $news = News::query()
                ->select(NewsListResource::NEWS_COLUMNS)
                ->whereIn('id', MarqueNews::query()->select('news_id'))
                ->where('published', true)
                ->with([
                    'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->orderByDesc('date')
                ->limit(10)
                ->get();

            return NewsListResource::collection($news)->resolve();
        });
    }

    /**
     * @return list<array{title: string, hash: string, url: string|null}>
     */
    private function breakingNews(): array
    {
        return Cache::remember(CacheKey::breakingNews(), now()->addMinutes(5), function () {
            $rows = BreakingNews::query()
                ->where('published', true)
                ->with([
                    'news' => fn ($q) => $q->select(['id', 'category_id', 'slug_key']),
                    'news.category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                    'news.category.parentRecursive.parent' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                ])
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $request = request();

            return $rows->map(function (BreakingNews $bn) use ($request) {
                $url = null;
                if ($bn->news !== null) {
                    $payload = (new NewsListResource($bn->news))->toArray($request);
                    $url = $payload['url'] ?? null;
                }

                return [
                    'title' => $bn->title,
                    'hash' => $bn->hash,
                    'url' => $url,
                ];
            })->values()->all();
        });
    }
}
