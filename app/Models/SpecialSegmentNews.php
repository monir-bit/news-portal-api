<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialSegmentNews extends Model
{
    protected $fillable = [
        'special_segment_id',
        'news_id',
        'position',
    ];

    public function specialSegment(): BelongsTo
    {
        return $this->belongsTo(SpecialSegment::class);
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
