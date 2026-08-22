<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CommentCardResource;
use App\Models\CommentNewsCard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentCardController extends Controller
{
    /**
     * Latest comment cards for the homepage slider.
     */
    public function commentCardSummary(): Collection
    {
        return CommentNewsCard::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'image']);
    }

    /**
     * A single comment card plus recent others (last 30 days), for the details view.
     *
     * @return array{current_card: CommentCardResource|null, others_card: AnonymousResourceCollection|array<int, mixed>}
     */
    public function details(int $id): array
    {
        if (! CommentNewsCard::find($id)) {
            return [
                'current_card' => null,
                'others_card' => [],
            ];
        }

        $currentCard = CommentNewsCard::with('news.category.parentRecursive')
            ->where('id', $id)
            ->whereHas('news', fn ($query) => $query->where('published', true))
            ->first();

        $othersCard = CommentNewsCard::with('news.category.parentRecursive')
            ->whereHas('news', fn ($query) => $query->where('published', true))
            ->where('created_at', '>=', now()->subDays(30))
            ->where('id', '!=', $id)
            ->orderByDesc('id')
            ->get();

        return [
            'current_card' => CommentCardResource::make($currentCard),
            'others_card' => CommentCardResource::collection($othersCard),
        ];
    }
}
