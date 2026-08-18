<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReporterNotice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'title',
        'content',
        'from',
        'is_active',
        'is_for_all',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_for_all' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReporterNoticeMedia::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReporterNoticeAssignment::class);
    }

    public function assignedReporters(): BelongsToMany
    {
        return $this->belongsToMany(Reporter::class, 'reporter_notice_assignments')
            ->withTimestamps();
    }

    public function readCounts(): HasMany
    {
        return $this->hasMany(ReporterNoticeReadCount::class);
    }

    public function opinions(): HasMany
    {
        return $this->hasMany(ReporterNoticeOpinion::class);
    }
}
