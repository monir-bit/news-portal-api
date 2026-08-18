<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterNoticeMedia extends Model
{
    protected $fillable = ['reporter_notice_id', 'media_type', 'media_url'];

    public function reporterNotice(): BelongsTo
    {
        return $this->belongsTo(ReporterNotice::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->media_type === 'image' && $this->media_url
            ? UtilsHelper::GetMediaUrl($this->media_url)
            : null;
    }
}
