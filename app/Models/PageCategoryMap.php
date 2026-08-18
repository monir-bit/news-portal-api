<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageCategoryMap extends Model
{
    protected $fillable = [
        'category_id',
        'date',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
