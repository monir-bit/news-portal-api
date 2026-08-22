<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarqueNews extends Model
{
    protected $fillable = [
        'news_id',
        'position',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
