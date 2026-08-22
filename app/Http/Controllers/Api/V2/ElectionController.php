<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\ElectionResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ElectionController extends Controller
{
    public function results(Request $request): array
    {
        $slug = $request->query('slug');
        $partyId = $request->query('party_id');

        $query = ElectionResult::with(['seat', 'party'])->orderBy('id');

        if ($partyId) {
            $query->where('election_party_id', (int) $partyId);
        }
        if ($slug) {
            $query->whereHas('party', fn (Builder $q) => $q->where('slug', $slug));
        }

        return [
            'results' => $query->get()->map(fn (ElectionResult $result) => $this->resultRow($result))->values()->toArray(),
        ];
    }

    public function summary(): array
    {
        return [
            'election_title' => 'ত্রয়োদশ জাতীয় সংসদ নির্বাচন ২০২৬',
            'party_wise' => $this->buildPartyWiseSummary(
                ElectionResult::with('party')->get()
            ),
        ];
    }

    /**
     * @return array{id: int, party_id: int, party_slug: string|null, seat_name: string|null, candidate_name: string, party_name: string|null, logo_image: string|null, party_symbol: string|null, votes_received: int}
     */
    private function resultRow(ElectionResult $result): array
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

    /**
     * @return array<int, array{party_id: int, party_name: string, slug: string, seat_count: int, logo_image: string|null, party_symbol: string|null}>
     */
    private function buildPartyWiseSummary(Collection $results): array
    {
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
}
