<?php

namespace App\Http\Resources;

use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'description' => $this->description,
            'page' => $this->page instanceof \BackedEnum ? $this->page->value : (string) $this->page,
            'is_active' => (bool) $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'options' => PollOptionResource::collection($this->options),
            'options_sum_votes_count' => (int) (
                $this->options_sum_votes_count
                ?? $this->options->sum('votes_count')
            ),
            'user_has_voted' => $this->resolveUserHasVoted($request),
        ];
    }

    /**
     * Perf fix vs. the old app: when the controller has already computed the current
     * IP's voted poll ids in bulk (see `PollController::index()`), it stashes the result
     * on the model as a transient `user_has_voted` attribute — read that instead of
     * running a fresh `PollVote` existence query per resource instance (N+1 in listings).
     * Falls back to a single live query for single-poll endpoints (show/vote/firstByPage)
     * where there is no N+1 to avoid.
     */
    private function resolveUserHasVoted(Request $request): bool
    {
        if (array_key_exists('user_has_voted', $this->resource->getAttributes())) {
            return (bool) $this->resource->getAttributes()['user_has_voted'];
        }

        $ip = $request->ip();

        return $ip ? PollVote::query()
            ->where('poll_id', $this->id)
            ->where('ip_address', $ip)
            ->exists() : false;
    }
}
