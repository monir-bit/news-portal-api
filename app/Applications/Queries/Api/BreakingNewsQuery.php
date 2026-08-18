<?php

namespace App\Applications\Queries\Api;

use App\Applications\Cache\CacheKey;
use App\Http\Resources\Api\NewsListResource;
use App\Models\BreakingNews;
use Illuminate\Support\Facades\Cache;

class BreakingNewsQuery
{
    /**
     * @return list<array{title: string, hash: string, url: string|null}>
     */
    public function handle(): array
    {
        return Cache::remember(CacheKey::breakingNews(), now()->addMinutes(5), function () {
            $rows = BreakingNews::query()
                ->where('published', true)
                ->with(['news.category.parentRecursive.parent'])
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
