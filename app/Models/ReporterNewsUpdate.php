<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporterNewsUpdate extends Model
{
    protected $fillable = ['reporter_news_id', 'content'];

    public function reporterNews(): BelongsTo
    {
        return $this->belongsTo(ReporterNews::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReporterNewsUpdateMedia::class);
    }
}
