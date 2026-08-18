<?php

namespace App\Models;

use App\Applications\Enums\IsShowReporterEnum;
use App\Applications\Helpers\UtilsHelper;
use App\Observers\NewsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

#[ObservedBy(NewsObserver::class)]
class News extends Model
{
    use SoftDeletes, Searchable;

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

    protected $casts = [
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


    public function toSearchableArray()
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
            'content' => strip_tags($this->details?->details),
            'tags' => $this->tags->pluck('name')->implode(' '),
            'category' => $this->category?->name,
            'published' => $this->published,
            'date' => $this->date?->timestamp,
        ];
    }

    /**
     * Get the category that owns the news
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the news
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function liveNews()
    {
        return $this->hasOne(LiveNews::class);
    }

    /**
     * Get the user who last updated the news
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function workingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'working_by');
    }

    public function details()
    {
        return $this->hasOne(NewsDetails::class);
    }

    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'news_tag_mappings', // pivot table name
            'news_id',           // foreign key on pivot for News
            'tag_id'             // foreign key on pivot for Tag
        );
    }

    public function latestNews()
    {
        return $this->hasOne(LatestNews::class);
    }

    public function marqueeNews()
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

    public function timelineNews()
    {
        return $this->hasMany(NewsTimeline::class);
    }

    public function correspondence()
    {
        return $this->hasOne(NewsCorrespondent::class);
    }

    public function getImageAttribute($value)
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function sectionLayoutNews(): HasOne
    {
        return $this->hasOne(LayoutSectionNews::class, 'news_id', 'id');
    }

    public function categoryLayoutNews(): HasOne
    {
        return $this->hasOne(CategoryLayoutNews::class, 'news_id', 'id')->where('category_id', $this->category_id);
    }

    public function categoryPageLayoutNews(): HasMany
    {
        return $this->hasMany(CategoryPageLayoutNews::class, 'news_id', 'id');
    }

    public function newsReads(): HasMany
    {
        return $this->hasMany(NewsRead::class);
    }

    public function authors()
    {
        return $this->belongsToMany(
            Author::class,
            'author_news_mappings',
            'news_id',
            'author_id'
        );
    }

    public function webStory()
    {
        return $this->hasOne(WebStory::class);
    }

    public function specialTags()
    {
        return $this->belongsToMany(
            SpecialTag::class,
            'special_tag_news',
            'news_id',
            'special_tag_id'
        )->withPivot('position');
    }

    public function reporters()
    {
        return $this->belongsToMany(
            Reporter::class,
            'reporter_news',
            'news_id',
            'reporter_id'
        )->withTimestamps();
    }

    public function newsLocations()
    {
        return $this->hasMany(NewsLocation::class);
    }

    public function reporterNewsMedia()
    {
        return $this->hasMany(ReporterNewsMedia::class);
    }

    public function reporterNews()
    {
        return $this->hasMany(ReporterNews::class);
    }

    public function activityHistory()
    {
        return $this->hasMany(NewsActivityHistory::class);
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
}
