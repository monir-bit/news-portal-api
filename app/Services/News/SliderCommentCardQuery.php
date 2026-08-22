<?php

namespace App\Services\News;

use App\Models\CommentNewsCard;
use Illuminate\Support\Collection;

class SliderCommentCardQuery
{
    public function handle(): Collection
    {
        return CommentNewsCard::orderByDesc('created_at')->limit(10)->get(['id', 'image']);
    }
}
