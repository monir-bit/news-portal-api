<?php

namespace App\Models;

use App\Support\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionParty extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'symbol_image',
        'party_symbol',
        'party_symbol_text',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(ElectionResult::class, 'election_party_id');
    }

    public function getSymbolImageAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }

    public function getPartySymbolAttribute($value): ?string
    {
        return UtilsHelper::GetMediaUrl($value);
    }
}
