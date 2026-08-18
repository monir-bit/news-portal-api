<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Editorial snapshot for a {@see ReporterNews} row; desk “Save record” bumps parent {@see ReporterNews::$received_edit} to pending.
 */
class ReporterNewsEdit extends Model
{
    protected $fillable = [
        'reporter_news_id',
        'title',
        'content',
        'created_by',
    ];

    public function reporterNews(): BelongsTo
    {
        return $this->belongsTo(ReporterNews::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
