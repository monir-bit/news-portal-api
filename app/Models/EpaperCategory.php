<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpaperCategory extends Model
{
    protected $fillable = [
        'title',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EpaperQuestion::class, 'epaper_category_id');
    }
}
