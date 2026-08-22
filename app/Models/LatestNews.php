<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatestNews extends Model
{
    protected $table = 'latest_news';

    protected $fillable = [
        'news_id',
        'position',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
