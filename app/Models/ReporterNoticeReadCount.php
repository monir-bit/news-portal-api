<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterNoticeReadCount extends Model
{
    protected $fillable = ['reporter_notice_id', 'reporter_id', 'read_count'];

    protected $casts = [
        'read_count' => 'integer',
    ];

    public function reporterNotice(): BelongsTo
    {
        return $this->belongsTo(ReporterNotice::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }
}
