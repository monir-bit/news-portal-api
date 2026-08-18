<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReporterPrintNews extends Model
{
    protected $table = 'reporter_print_news';

    protected $fillable = ['reporter_id', 'reporter_news_id', 'title', 'content', 'is_complete', 'is_working', 'working_by'];

    protected $casts = [
        'is_complete' => 'boolean',
        'is_working' => 'boolean',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function reporterNews(): BelongsTo
    {
        return $this->belongsTo(ReporterNews::class);
    }

    public function reporterPrintNewsMedia(): HasMany
    {
        return $this->hasMany(ReporterPrintNewsMedia::class);
    }

    public function workingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'working_by');
    }
}
