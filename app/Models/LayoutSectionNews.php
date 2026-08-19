<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayoutSectionNews extends Model
{
    protected $guarded = ['id'];

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }

    public function LayoutSection()
    {
        return $this->belongsTo(LayoutSection::class, 'layout_section_id', 'id');
    }
}
