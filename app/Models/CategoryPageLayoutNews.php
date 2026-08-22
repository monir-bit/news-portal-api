<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryPageLayoutNews extends Model
{
    protected $guarded = ['id'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function categoryPageLayout(): BelongsTo
    {
        return $this->belongsTo(CategoryPageLayout::class);
    }
}
