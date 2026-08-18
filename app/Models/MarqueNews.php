<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarqueNews extends Model
{
    protected $fillable = [
        'news_id',
        'position',
    ];

    /**
     * Get the news that owns the marquee news
     */
    public function news()
    {
        return $this->belongsTo(News::class);
    }
}
