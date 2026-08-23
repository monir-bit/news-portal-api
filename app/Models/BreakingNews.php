<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakingNews extends Model
{
    protected $fillable = [
        'news_id',
        'hash',
        'title',
        'published',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
