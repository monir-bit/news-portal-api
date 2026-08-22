<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveNews extends Model
{
    protected $fillable = ['news_id', 'position', 'title', 'content', 'is_active', 'stopped_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'stopped_at' => 'datetime',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
