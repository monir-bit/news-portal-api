<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsDetails extends Model
{
    protected $guarded = ['id'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
