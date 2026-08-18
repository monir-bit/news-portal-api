<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterPrintNewsMedia extends Model
{
    protected $fillable = ['reporter_id', 'reporter_print_news_id', 'media_type', 'media_url'];

    protected $appends = ['image_url'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function reporterPrintNews(): BelongsTo
    {
        return $this->belongsTo(ReporterPrintNews::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->media_url ? UtilsHelper::GetMediaUrl($this->media_url) : null;
    }
}
