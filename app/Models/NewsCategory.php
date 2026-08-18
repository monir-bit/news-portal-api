<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsCategory extends Model
{
    protected $fillable = [
        'news_id',
        'category_id',
    ];

    public function news(){
        return $this->belongsTo(News::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
}
