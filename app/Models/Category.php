<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    protected $casts = [
        'has_page' => 'boolean',
    ];

    /**
     * Parent category (belongs to)
     */
    public function parent()
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    /**
     * Direct children categories
     */
    public function children()
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        )->orderBy('position');
    }

    /**
     * Recursive children (tree)
     */
    public function childrenRecursive()
    {
        return $this->children()->with([
            'childrenRecursive',
            'parentRecursive',
        ]);
    }

    public function parentRecursive()
    {
        return $this->parent()->with('parentRecursive');
    }

    public function categoryPageLayouts()
    {
        return $this->hasMany(CategoryPageLayout::class, 'category_id', 'id')->orderBy('position');
    }

    public function categorySeo(): HasOne
    {
        return $this->hasOne(CategorySeo::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function questions()
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

    /**
     * This category's id plus every descendant id, resolved by slug.
     * Used to scope a news listing to a category and all of its subcategories.
     *
     * @return array<int, int>
     */
    public static function idsForSlug(string $slug): array
    {
        $category = static::where('slug', $slug)->with('childrenRecursive')->first();

        if (! $category) {
            return [];
        }

        return [...$category->descendantIds(), $category->id];
    }
}
