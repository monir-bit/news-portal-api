<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterNewsMedia extends Model
{
    protected $fillable = ['reporter_id', 'news_id', 'media_type', 'media_url'];

    protected $appends = ['image_url'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->media_url ? UtilsHelper::GetMediaUrl($this->media_url) : null;
    }
}
