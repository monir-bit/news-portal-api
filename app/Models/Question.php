<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'description',
        'is_active',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Question $question): void {
            $question->options()->delete();
        });
    }

    /**
     * Not nullable-safe by design (matches old app): a question with a null
     * start_time or end_time is never considered active.
     */
    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class);
    }
}
