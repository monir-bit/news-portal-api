<?php

namespace App\Models;

use App\Applications\Helpers\UtilsHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommentNewsCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'news_id',
        'image',
        'commenter_image',
        'short_description',
        'position',
        'date',
        'is_publish',
        'commenter',
        'comment',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_publish' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getImageUrlAttribute(): ?string
    {
        return UtilsHelper::GetMediaUrl($this->image);
    }

    public function getCommenterImageUrlAttribute(): ?string
    {
        return UtilsHelper::GetMediaUrl($this->commenter_image);
    }
}
