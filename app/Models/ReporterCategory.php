<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterCategory extends Model
{
    protected $fillable = ['reporter_id', 'category_id'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
