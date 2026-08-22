<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Minimal, read-only slice needed to resolve `News::reporterNews.reporter`
 * for the public API's `NewsDetailsResource.reporter` byline block.
 */
class ReporterNews extends Model
{
    protected $table = 'reporter_news';

    protected $fillable = ['reporter_id', 'news_id'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
