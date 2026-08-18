<?php

namespace App\Models;

use App\Models\Concerns\SoftDeletesWithDeleter;
use App\Support\EpaperApiCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpaperPublication extends Model
{
    use SoftDeletesWithDeleter;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'deleted_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (EpaperPublication $publication): void {
            if ($publication->isForceDeleting()) {
                return;
            }

            foreach ($publication->editions as $edition) {
                $edition->delete();
            }
        });

        static::saved(function (EpaperPublication $publication): void {
            $formerSlug = $publication->wasChanged('slug')
                ? (string) $publication->getOriginal('slug')
                : null;
            EpaperApiCache::forgetPublicationRelated($publication, $formerSlug);
        });
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function editions(): HasMany
    {
        return $this->hasMany(EpaperEdition::class, 'epaper_publication_id');
    }
}
