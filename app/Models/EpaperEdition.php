<?php

namespace App\Models;

use App\Models\Concerns\SoftDeletesWithDeleter;
use App\Support\EpaperApiCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class EpaperEdition extends Model
{
    use SoftDeletesWithDeleter;

    protected $fillable = [
        'epaper_publication_id',
        'publication_date',
        'title',
        'print_issue_ref',
        'status',
        'revision',
        'derived_from_edition_id',
    ];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'revision' => 'integer',
            'deleted_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (EpaperEdition $edition): void {
            $edition->loadMissing('publication');
            if ($edition->publication) {
                EpaperApiCache::forgetPublicationsList();
                $ymd = \Illuminate\Support\Carbon::parse($edition->publication_date)->format('Y-m-d');
                EpaperApiCache::forgetReaderPayloadsForSlugDate($edition->publication->slug, $ymd);
            }

            if ($edition->isForceDeleting()) {
                return;
            }

            foreach ($edition->pages as $page) {
                $page->delete();
            }
        });

        static::saved(function (EpaperEdition $edition): void {
            EpaperApiCache::forgetPublicationListAndReaderForEdition($edition);
            if ($edition->wasChanged('publication_date')) {
                $edition->loadMissing('publication');
                if ($edition->publication) {
                    $raw = $edition->getOriginal('publication_date');
                    if ($raw !== null) {
                        $oldYmd = \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
                        EpaperApiCache::forgetReaderPayloadsForSlugDate($edition->publication->slug, $oldYmd);
                    }
                }
            }
        });
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(EpaperPublication::class, 'epaper_publication_id');
    }

    public function derivedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'derived_from_edition_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(EpaperEditionPage::class, 'epaper_edition_id')->orderBy('page_number');
    }

    public function regions(): HasManyThrough
    {
        return $this->hasManyThrough(
            EpaperRegion::class,
            EpaperEditionPage::class,
            'epaper_edition_id',
            'epaper_edition_page_id',
            'id',
            'id'
        );
    }

    /**
     * Reader / public API: only editions explicitly marked published are exposed.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
