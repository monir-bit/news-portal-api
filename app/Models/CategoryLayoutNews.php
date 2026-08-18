<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryLayoutNews extends Model
{
    protected $fillable = [
        'category_layout_id',
        'category_id',
        'news_id',
        'position',
    ];

    public function news() {
        return $this->belongsTo(News::class, 'news_id', 'id');
    }

}
