<?php

namespace App\Models;

use App\Enums\IsShowReporterEnum;
use App\Http\Resources\NewsListResource;
use App\Support\PortalDateHelper;
use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

class News extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        'shoulder',
        'slug_key',
        'title',
        'ticker',
        'sort_description',
        'order',
        'proofreader',
        'image',
        'type',
        'published',
        'image_caption',
        'representative',
        'is_show_reporter',
        'latest',
        'news_marquee',
        'live_news',
        'is_thread',
        'is_visible_shoulder',
        'is_visible_ticker',
        'category_id',
        'date',
        'created_by',
        'updated_by',
        'old_hash_key',
        'is_working',
        'working_by',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'latest' => 'boolean',
            'news_marquee' => 'boolean',
            'live_news' => 'boolean',
            'is_thread' => 'boolean',
            'is_visible_shoulder' => 'boolean',
            'is_visible_ticker' => 'boolean',
            'is_show_reporter' => IsShowReporterEnum::class,
            'is_working' => 'boolean',
            'date' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['details', 'tags']);

        return [
            'id' => $this->id,
            'slug_key' => $this->slug_key,
            'title' => $this->title,
            'shoulder' => $this->shoulder,
            'ticker' => $this->ticker,
            'sort_description' => $this->sort_description,
            'representative' => $this->representative,
            'content' => strip_tags((string) $this->details?->details),
            'tags' => $this->tags->pluck('name')->implode(' '),
            'category' => $this->category?->name,
            'published' => $this->published,
            'date' => $this->date?->timestamp,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function liveNews(): HasOne
    {
        return $this->hasOne(LiveNews::class);
    }

    public function details(): HasOne
    {
        return $this->hasOne(NewsDetails::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'news_tag_mappings',
            'news_id',
            'tag_id'
        );
    }

    public function latestNews(): HasOne
    {
        return $this->hasOne(LatestNews::class);
    }

    public function marqueeNews(): HasOne
    {
        return $this->hasOne(MarqueNews::class);
    }

    /**
     * Thank-news card / entry linked to this news (one per news).
     */
    public function thankNews(): HasOne
    {
        return $this->hasOne(ThankNews::class);
    }

    public function timelineNews(): HasMany
    {
        return $this->hasMany(NewsTimeline::class);
    }

    public function getImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function sectionLayoutNews(): HasOne
    {
        return $this->hasOne(LayoutSectionNews::class, 'news_id', 'id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            Author::class,
            'author_news_mappings',
            'news_id',
            'author_id'
        );
    }

    public function webStory(): HasOne
    {
        return $this->hasOne(WebStory::class);
    }

    public function newsLocations(): HasMany
    {
        return $this->hasMany(NewsLocation::class);
    }

    public function reporterNews(): HasMany
    {
        return $this->hasMany(ReporterNews::class);
    }

    public function linkedNewsRows(): HasMany
    {
        return $this->hasMany(LinkedNews::class, 'main_news_id')->orderBy('position');
    }

    public function newsImages(): HasMany
    {
        return $this->hasMany(NewsImage::class)->orderBy('position');
    }

    public function newsSeo(): HasOne
    {
        return $this->hasOne(NewsSeo::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /**
     * Most-read news in the last 24h, optionally scoped to a set of category ids.
     * Powers the site-wide and per-category "most read" rails.
     *
     * Resolve ids first via a lightweight query, then a second column-selected
     * query with a 3-level category eager load, then re-sort in PHP to match
     * the id order. This is the standard "most read / latest" query pattern
     * used across the API — reuse it wherever a similar rail is built.
     *
     * @param  array<int, int>|null  $categoryIds
     */
    public static function mostRead(?array $categoryIds = null, int $limit = 15): Collection
    {
        $mostReadIds = NewsRead::query()
            ->select('news_id')
            ->whereHas('news', fn (Builder $q) => $q->published()->whereBetween('date', [
                PortalDateHelper::subDay(),
                PortalDateHelper::now(),
            ]))
            ->when($categoryIds, fn (Builder $q) => $q->whereIn('category_id', $categoryIds))
            ->groupBy('news_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('news_id');

        return static::query()
            ->select(NewsListResource::NEWS_COLUMNS)
            ->whereIn('id', $mostReadIds)
            ->with([
                'category' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
                'category.parentRecursive.parentRecursive' => fn ($q) => $q->select(NewsListResource::CATEGORY_COLUMNS),
            ])
            ->get()
            ->sortBy(fn (self $news) => $mostReadIds->search($news->id))
            ->values();
    }
}
