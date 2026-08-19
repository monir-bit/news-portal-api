<?php

namespace App\Observers;

use Rakibmiah99\AgamirsomoySharedCache\CacheKey;
use App\Applications\Helpers\UtilsHelper;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NewsObserver
{
    /**
     * Handle the News "creating" event.
     */
    public function creating(News $news): void
    {
        $news->slug_key = UtilsHelper::generateUniqueNewsSlugKey();
        $news->date = now();
        $news->created_by = auth()->id();
    }

    /**
     * Keep updated_by on the same UPDATE as other columns (avoids a second round-trip).
     */
    public function updating(News $news): void
    {
        if (auth()->check()) {
            $news->updated_by = auth()->id();
            if (! $news->created_by) {
                $news->created_by = auth()->id();
            }
        }
    }

    /**
     * Handle the News "created" event.
     */
    public function created(News $news): void
    {
        if ($news->published) {
            $this->dispatchSitemapJob([$this->newsDate($news)]);
        }

        $news->searchable();
    }

    /**
     * Handle the News "updated" event.
     */
    public function updated(News $news): void
    {
        Cache::forget(CacheKey::newsDetails($news->slug_key));

        if ($this->shouldRefreshSitemap($news)) {
            $this->dispatchSitemapJob($this->sitemapDatesForNews($news));
        }

        if ($news->wasChanged('published') && $news->published) {
            $news->searchable();
        }
    }

    /**
     * Handle the News "deleting" event.
     */
    public function deleting(News $news): void
    {
        Cache::forget(CacheKey::newsDetails($news->slug_key));

        if ($news->published) {
            $this->dispatchSitemapJob([$this->newsDate($news)]);
        }
    }

    private function shouldRefreshSitemap(News $news): bool
    {
        if ((bool) $news->published) {
            return true;
        }

        return $news->wasChanged('published') && (bool) $news->getOriginal('published');
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function dispatchSitemapJob(array $dates): void
    {
        $dates = array_values(array_unique(array_filter($dates)));

        if ($dates === []) {
            return;
        }
    }

    /**
     * @return array<int, string>
     */
    private function sitemapDatesForNews(News $news): array
    {
        $dates = [$this->newsDate($news)];

        if ($news->wasChanged('date')) {
            $originalDate = $news->getOriginal('date');
            if ($originalDate) {
                $dates[] = Carbon::parse($originalDate)->format('Y-m-d');
            }
        }

        return array_values(array_unique($dates));
    }

    private function newsDate(News $news): string
    {
        return ($news->date ?? $news->created_at)->format('Y-m-d');
    }

    /**
     * Handle the News "restored" event.
     */
    public function restored(News $news): void
    {
        //
    }

    /**
     * Handle the News "force deleted" event.
     */
    public function forceDeleted(News $news): void
    {
        //
    }
}
