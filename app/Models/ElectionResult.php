<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ElectionResult extends Model
{
    protected $fillable = [
        'election_seat_id',
        'election_party_id',
        'candidate_name',
        'votes_received',
    ];

    protected function casts(): array
    {
        return [
            'votes_received' => 'integer',
        ];
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(ElectionSeat::class, 'election_seat_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(ElectionParty::class, 'election_party_id');
    }

    /**
     * Flat result rows, optionally filtered by party id and/or party slug.
     *
     * @param  array{party_id?: int, slug?: string}  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filtered(array $filters = []): array
    {
        $query = self::query()->with(['seat', 'party'])->orderBy('id');

        if (! empty($filters['party_id'])) {
            $query->where('election_party_id', $filters['party_id']);
        }
        if (! empty($filters['slug'])) {
            $query->whereHas('party', fn ($q) => $q->where('slug', $filters['slug']));
        }

        return $query->get()
            ->map(fn (self $result) => self::toRow($result))
            ->values()
            ->toArray();
    }

    /**
     * Seat counts and branding grouped by party.
     *
     * @return array<int, array{party_id: int, party_name: string, slug: string, seat_count: int, logo_image: string|null, party_symbol: string|null}>
     */
    public static function partyWiseSummary(): array
    {
        $results = self::query()->with('party')->get();

        return $results->groupBy('election_party_id')
            ->map(function (Collection $partyResults, $partyId) {
                $party = $partyResults->first()?->party;

                return [
                    'party_id' => (int) $partyId,
                    'party_name' => $party?->name ?? '',
                    'slug' => $party?->slug ?? '',
                    'seat_count' => $partyResults->count(),
                    'logo_image' => $party?->symbol_image ?? null,
                    'party_symbol' => $party?->party_symbol ?? null,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private static function toRow(self $result): array
    {
        return [
            'id' => $result->id,
            'party_id' => $result->election_party_id,
            'party_slug' => $result->party?->slug,
            'seat_name' => $result->seat?->name,
            'candidate_name' => $result->candidate_name,
            'party_name' => $result->party?->name,
            'logo_image' => $result->party?->symbol_image,
            'party_symbol' => $result->party?->party_symbol,
            'votes_received' => $result->votes_received,
        ];
    }
}
