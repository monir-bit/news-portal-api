<?php

namespace App\Http\Controllers\Api;

use App\Applications\Queries\Api\ElectionResultQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ElectionController extends Controller
{
    public function results(Request $request, ElectionResultQuery $electionResultQuery)
    {
        $slug = $request->query('slug');
        $partyId = $request->query('party_id');

        $filters = [];
        if ($slug) {
            $filters['slug'] = $slug;
        }
        if ($partyId) {
            $filters['party_id'] = (int) $partyId;
        }

        return [
            'results' => $electionResultQuery->handleFiltered($filters),
        ];
    }

    public function summary(ElectionResultQuery $electionResultQuery)
    {
        return $electionResultQuery->handlePartyWise();
    }
}
