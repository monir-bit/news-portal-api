<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Reporter extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'designation',
        'alternate_designation',
        'joining_date',
        'image',
        'password',
        'is_active',
        'has_location',
        'reporter_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'is_active' => 'boolean',
        'has_location' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'reporter_categories')
            ->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ReporterLocation::class);
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'reporter_news')
            ->withTimestamps();
    }

    public function reporterNewsMedia(): HasMany
    {
        return $this->hasMany(ReporterNewsMedia::class);
    }

    public function reporterPrintNews(): HasMany
    {
        return $this->hasMany(ReporterPrintNews::class);
    }

    public function getImageAttribute($value): ?string
    {
        return $value ? UtilsHelper::GetMediaUrl($value) : null;
    }
}
