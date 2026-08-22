<?php

namespace App\Models\Concerns;

use App\Support\UtilsHelper;

trait HasMemberImage
{
    public function getImageAttribute(?string $value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    /**
     * Raw stored path (`image` column).
     */
    public function getImagePathAttribute(): ?string
    {
        $path = $this->attributes['image'] ?? null;

        return $path !== null && $path !== '' ? (string) $path : null;
    }
}
