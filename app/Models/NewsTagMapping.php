<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsTagMapping extends Model
{
    protected $guarded = ['id'];

    public function news() {
        return $this->belongsTo(News::class, 'news_id');
    }
}
