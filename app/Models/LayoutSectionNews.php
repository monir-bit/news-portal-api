<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LayoutSectionNews extends Model
{
    protected $guarded = ['id'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }

    public function layoutSection(): BelongsTo
    {
        return $this->belongsTo(LayoutSection::class, 'layout_section_id', 'id');
    }
}
