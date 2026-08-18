<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryPageLayoutNews extends Model
{
    protected $fillable = [
        'category_page_layout_id',
        'news_id',
        'position',
    ];

    public function categoryPageLayout()
    {
        return $this->belongsTo(CategoryPageLayout::class, 'category_page_layout_id', 'id');
    }

    public function news()
    {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }
}
