<?php

namespace App\Applications\Queries\Api;

use App\Models\ElectionResult;
use Illuminate\Support\Collection;

class ElectionResultQuery
{
    public function handle(): array
    {
        $results = ElectionResult::with(['seat', 'party'])
            ->orderBy('id')
            ->get()
            ->map(function (ElectionResult $result) {
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
            })
            ->values()
            ->toArray();

        $partyWise = $this->buildPartyWiseSummary(
            ElectionResult::with('party')->get()
        );

        return [
            'election_title' => 'ত্রয়োদশ জাতীয় সংসদ নির্বাচন ২০২৬',
            'party_wise' => $partyWise,
            'results' => $results,
        ];
    }

    /**
     * @param  array{party_id?: int, slug?: string}  $filters
     */
    public function handleFiltered(array $filters = []): array
    {
        $query = ElectionResult::with(['seat', 'party'])->orderBy('id');

        if (! empty($filters['party_id'])) {
            $query->where('election_party_id', $filters['party_id']);
        }
        if (! empty($filters['slug'])) {
            $query->whereHas('party', fn ($q) => $q->where('slug', $filters['slug']));
        }

        return $query->get()
            ->map(function (ElectionResult $result) {
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
            })
            ->values()
            ->toArray();
    }

    public function handlePartyWise(): array
    {
        return [
            'election_title' => 'ত্রয়োদশ জাতীয় সংসদ নির্বাচন ২০২৬',
            'party_wise' => $this->buildPartyWiseSummary(
                ElectionResult::with('party')->get()
            ),
        ];
    }

    /**
     * @return array<int, array{party_id: int, party_name: string, slug: string, seat_count: int, logo_image: string|null, party_symbol: string|null}>
     */
    private function buildPartyWiseSummary(Collection $results): array
    {
        $grouped = $results->groupBy('election_party_id');

        return $grouped->map(function (Collection $partyResults, $partyId) {
            $party = $partyResults->first()?->party;

            return [
                'party_id' => (int) $partyId,
                'party_name' => $party?->name ?? '',
                'slug' => $party?->slug ?? '',
                'seat_count' => $partyResults->count(),
                'logo_image' => $party?->symbol_image ?? null,
                'party_symbol' => $party?->party_symbol ?? null,
            ];
        })->values()->toArray();
    }
}

