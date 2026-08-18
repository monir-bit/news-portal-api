<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialTagNews extends Model
{
    protected $fillable = [
        'news_id',
        'special_tag_id',
        'position',
    ];

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    public function specialTag()
    {
        return $this->belongsTo(SpecialTag::class);
    }
}
