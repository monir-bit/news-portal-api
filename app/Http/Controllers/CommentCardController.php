<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentCardResource;
use App\Models\CommentNewsCard;
use App\Services\News\SliderCommentCardQuery;
use Illuminate\Database\Eloquent\Collection;

class CommentCardController extends Controller
{
    /**
     * Max "other" comment cards returned alongside a single card's details.
     * The old app returned this list unbounded; capped here to avoid an
     * unbounded result set on a card that has been live for a long time.
     */
    private const OTHERS_LIMIT = 30;

    public function commentCardSummary(SliderCommentCardQuery $sliderCommentCardQuery): Collection
    {
        return $sliderCommentCardQuery->handle();
    }

    /**
     * @return array<string, mixed>
     */
    public function details(int $id): array
    {
        // Single query for the current card (with the same publish/relation
        // constraints as the old app), instead of an existence check followed
        // by a second, near-identical query.
        $currentCard = CommentNewsCard::with('news.category.parentRecursive')
            ->where('id', $id)
            ->whereHas('news', function ($query) {
                $query->where('published', true);
            })
            ->first();

        if (! $currentCard) {
            return [
                'current_card' => null,
                'others_card' => [],
            ];
        }

        $othersCard = CommentNewsCard::with('news.category.parentRecursive')
            ->whereHas('news', function ($query) {
                $query->where('published', true);
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->where('id', '!=', $id)
            ->orderByDesc('id')
            ->limit(self::OTHERS_LIMIT)
            ->get();

        return [
            'current_card' => CommentCardResource::make($currentCard),
            'others_card' => CommentCardResource::collection($othersCard),
        ];
    }
}
