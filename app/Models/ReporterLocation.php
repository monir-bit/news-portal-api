<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporterLocation extends Model
{
    protected $fillable = ['reporter_id', 'division_id', 'district_id', 'upazila_id'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Reporter::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }
}
