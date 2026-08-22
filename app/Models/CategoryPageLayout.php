<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryPageLayout extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_enable' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function layoutNews(): HasMany
    {
        return $this->hasMany(CategoryPageLayoutNews::class)->orderBy('position');
    }
}
