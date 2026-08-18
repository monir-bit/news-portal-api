<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialSegmentNews extends Model
{
    protected $fillable = [
        'special_segment_id',
        'news_id',
        'position',
    ];

    public function specialSegment()
    {
        return $this->belongsTo(SpecialSegment::class);
    }

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}
