<?php

namespace App\Models;

use App\Support\EpaperApiCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpaperQuestionOption extends Model
{
    protected $fillable = [
        'epaper_question_id',
        'option_text',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EpaperQuestion::class, 'epaper_question_id');
    }

    protected static function booted(): void
    {
        static::saved(function (EpaperQuestionOption $option): void {
            $option->loadMissing('question');
            if ($option->question) {
                EpaperApiCache::forgetQuestionSlots($option->question);
            }
        });

        static::deleted(function (EpaperQuestionOption $option): void {
            $option->loadMissing('question');
            if ($option->question) {
                EpaperApiCache::forgetQuestionSlots($option->question);
            }
        });
    }
}
