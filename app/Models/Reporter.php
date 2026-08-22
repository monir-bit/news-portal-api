<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Minimal, read-only slice of the reporter record needed by the public API's
 * `NewsDetailsResource.reporter` byline block. The full reporter auth/CMS
 * feature is out of scope for this phase.
 */
class Reporter extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'designation',
        'alternate_designation',
        'image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(ReporterNews::class);
    }

    public function getImageAttribute($value): ?string
    {
        return $value ? UtilsHelper::GetMediaUrl($value) : null;
    }
}
