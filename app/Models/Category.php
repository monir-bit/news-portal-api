<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'position',
        'visible',
        'has_page',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'has_page' => 'boolean',
            'visible' => 'boolean',
        ];
    }

    /**
     * Parent category (belongs to).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Direct children categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /**
     * Recursive children (tree).
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with([
            'childrenRecursive',
            'parentRecursive',
        ]);
    }

    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    public function categorySeo(): HasOne
    {
        return $this->hasOne(CategorySeo::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * All descendant category ids beneath this category (not including itself).
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        foreach ($this->childrenRecursive as $child) {
            $ids[] = $child->id;
            $ids = [...$ids, ...$child->descendantIds()];
        }

        return $ids;
    }
}
