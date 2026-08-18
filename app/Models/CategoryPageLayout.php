<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryPageLayout extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'position',
        'is_enable',
        'max_news',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function layoutNews()
    {
        return $this->hasMany(CategoryPageLayoutNews::class, 'category_page_layout_id', 'id');
    }
}
