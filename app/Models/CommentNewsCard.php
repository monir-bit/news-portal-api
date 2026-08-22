<?php

namespace App\Models;

use App\Support\UtilsHelper;
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

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_publish' => 'boolean',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
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
