<?php

namespace App\Http\Controllers;

use App\Models\ElectionResult;
use Illuminate\Http\Request;

class ElectionController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function results(Request $request): array
    {
        $filters = [];

        if ($slug = $request->query('slug')) {
            $filters['slug'] = $slug;
        }
        if ($partyId = $request->query('party_id')) {
            $filters['party_id'] = (int) $partyId;
        }

        return [
            'results' => ElectionResult::filtered($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'election_title' => 'ত্রয়োদশ জাতীয় সংসদ নির্বাচন ২০২৬',
            'party_wise' => ElectionResult::partyWiseSummary(),
        ];
    }
}
