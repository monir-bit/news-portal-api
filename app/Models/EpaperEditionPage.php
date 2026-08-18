<?php

namespace App\Models;

use App\Models\Concerns\SoftDeletesWithDeleter;
use App\Support\EpaperApiCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpaperEditionPage extends Model
{
    use SoftDeletesWithDeleter;

    protected $fillable = [
        'epaper_edition_id',
        'page_number',
        'image_path',
        'image_width_px',
        'image_height_px',
    ];

    protected function casts(): array
    {
        return [
            'deleted_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (EpaperEditionPage $page): void {
            $page->loadMissing('edition.publication');
            if ($page->edition && $page->edition->publication) {
                EpaperApiCache::forgetPublicationListAndReaderForEdition($page->edition);
            }

            if ($page->isForceDeleting()) {
                return;
            }

            foreach ($page->regions as $region) {
                if ($region->linked_region_id) {
                    EpaperRegion::whereKey($region->linked_region_id)->update(['linked_region_id' => null]);
                }
                EpaperRegion::where('linked_region_id', $region->id)->update(['linked_region_id' => null]);
                $region->delete();
            }
        });

        static::saved(function (EpaperEditionPage $page): void {
            $page->loadMissing('edition.publication');
            if ($page->edition && $page->edition->publication) {
                EpaperApiCache::forgetPublicationListAndReaderForEdition($page->edition);
            }
        });
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EpaperEdition::class, 'epaper_edition_id');
    }

    public function regions(): HasMany
    {
        return $this->hasMany(EpaperRegion::class, 'epaper_edition_page_id');
    }
}
