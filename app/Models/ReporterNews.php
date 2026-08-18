<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReporterNews extends Model
{
    protected $fillable = ['reporter_id', 'news_id', 'original_content', 'is_special', 'received_updated', 'received_edit', 'is_print_user'];

    protected $casts = [
        'is_special' => 'boolean',
        'is_print_user' => 'boolean',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ReporterNewsUpdate::class);
    }

    public function newsEdits(): HasMany
    {
        return $this->hasMany(ReporterNewsEdit::class);
    }

    public function reporterPrintNews(): HasOne
    {
        return $this->hasOne(ReporterPrintNews::class);
    }
}
