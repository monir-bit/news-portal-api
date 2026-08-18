<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkedNews extends Model
{
    protected $fillable = [
        'main_news_id',
        'linked_news_id',
        'position',
    ];

    public function mainNews(): BelongsTo
    {
        return $this->belongsTo(News::class, 'main_news_id');
    }

    public function linkedArticle(): BelongsTo
    {
        return $this->belongsTo(News::class, 'linked_news_id');
    }
}
