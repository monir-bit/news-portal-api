<?php

namespace App\Models;

use App\Models\Concerns\SoftDeletesWithDeleter;
use App\Support\EpaperApiCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpaperRegion extends Model
{
    use SoftDeletesWithDeleter;

    public const ROLE_HEAD = 'head';

    public const ROLE_TAIL = 'tail';

    protected $fillable = [
        'epaper_edition_page_id',
        'role',
        'title',
        'external_url',
        'x_pct',
        'y_pct',
        'width_pct',
        'height_pct',
        'crop_image_path',
        'editor_temp_key',
        'linked_region_id',
        'news_id',
    ];

    protected function casts(): array
    {
        return [
            'x_pct' => 'decimal:4',
            'y_pct' => 'decimal:4',
            'width_pct' => 'decimal:4',
            'height_pct' => 'decimal:4',
            'deleted_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (EpaperRegion $region): void {
            $region->loadMissing('page.edition.publication');
            $edition = $region->page?->edition;
            if ($edition && $edition->publication) {
                EpaperApiCache::forgetPublicationListAndReaderForEdition($edition);
            }
        });

        static::deleted(function (EpaperRegion $region): void {
            $region->loadMissing('page.edition.publication');
            $edition = $region->page?->edition;
            if ($edition && $edition->publication) {
                EpaperApiCache::forgetPublicationListAndReaderForEdition($edition);
            }
        });
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(EpaperEditionPage::class, 'epaper_edition_page_id');
    }

    public function linkedRegion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_region_id');
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
