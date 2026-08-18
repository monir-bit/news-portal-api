<?php

namespace App\Applications\Queries\Api;

use App\Models\CommentNewsCard;
use Illuminate\Support\Collection;

class SliderCommentCardQuery
{
    public function handle(): Collection
    {
        return CommentNewsCard::orderBy('created_at', 'DESC')->limit(10)->get(['id', 'image']);
    }


}

