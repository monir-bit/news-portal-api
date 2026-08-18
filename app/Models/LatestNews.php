<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LatestNews extends Model
{
    protected $fillable = [
        'news_id',
        'position',
    ];

    public function news()
    {
        return $this->belongsTo(News::class);
    }
}
