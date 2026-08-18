<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorNewsMapping extends Model
{
    protected $table = 'author_news_mappings';

    protected $fillable = [
        'news_id',
        'author_id',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
