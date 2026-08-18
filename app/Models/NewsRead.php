<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsRead extends Model
{
    protected $casts = [
        'read_date' => 'date:Y-m-d',
        'read_count' => 'integer',
    ];

    protected $fillable = [
        'news_id',
        'category_id',
        'read_date',
        'read_count',
        'visitor_id',
    ];

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function news(){
        return $this->belongsTo(News::class);
    }

}
