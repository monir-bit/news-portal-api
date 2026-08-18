<?php

namespace App\Http\Resources\Api;

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
        $ip = $request->ip();

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
            'user_has_voted' => $ip ? PollVote::query()
                ->where('poll_id', $this->id)
                ->where('ip_address', $ip)
                ->exists() : false,
        ];
    }
}
