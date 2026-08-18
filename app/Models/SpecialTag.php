<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function news()
    {
        return $this->belongsToMany(
            News::class,
            'special_tag_news',
            'special_tag_id',
            'news_id'
        )->withPivot('position')->orderByPivot('position');
    }

    public function specialTagNews()
    {
        return $this->hasMany(SpecialTagNews::class);
    }
}
