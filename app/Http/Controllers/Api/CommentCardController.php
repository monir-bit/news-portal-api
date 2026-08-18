<?php

namespace App\Http\Controllers\Api;

use App\Applications\Queries\Api\SliderCommentCardQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CommentCardResource;
use App\Models\CommentNewsCard;

class CommentCardController extends Controller
{
    public function commentCardSummary(
        SliderCommentCardQuery $sliderCommentCardQuery,
    )
    {
        return $sliderCommentCardQuery->handle();
    }

    public function details($id){

        if (!CommentNewsCard::find($id)) {
            return [
                'current_card' => null,
                'others_card' => []
            ];
        }

        $currentCard = CommentNewsCard::with('news.category.parentRecursive')->where('id', $id)
            ->whereHas('news', function ($query) {
                $query->where('published', true);
            })
            ->first();
        $others_card = CommentNewsCard::with('news.category.parentRecursive')
            ->whereHas('news', function ($query) {
                $query->where('published', true);
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->where('id', '!=', $id)->orderByDesc('id')->get();
        return [
            'current_card' => CommentCardResource::make($currentCard),
            'others_card' => CommentCardResource::collection($others_card),

        ];
    }
}
