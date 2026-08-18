<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterNoticeOpinion extends Model
{
    protected $fillable = ['reporter_notice_id', 'reporter_id', 'content'];

    public function reporterNotice(): BelongsTo
    {
        return $this->belongsTo(ReporterNotice::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }
}
