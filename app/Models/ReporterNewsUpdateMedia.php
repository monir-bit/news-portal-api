<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterNewsUpdateMedia extends Model
{
    protected $fillable = ['reporter_news_update_id', 'media_type', 'media_url'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->media_url ? UtilsHelper::GetMediaUrl($this->media_url) : null;
    }

    public function reporterNewsUpdate(): BelongsTo
    {
        return $this->belongsTo(ReporterNewsUpdate::class);
    }
}
