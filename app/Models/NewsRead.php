<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsRead extends Model
{
    protected $fillable = [
        'news_id',
        'category_id',
        'read_date',
        'read_count',
        'visitor_id',
    ];

    protected function casts(): array
    {
        return [
            'read_date' => 'date:Y-m-d',
            'read_count' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
