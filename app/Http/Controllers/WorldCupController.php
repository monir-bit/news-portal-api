<?php

namespace App\Http\Controllers;

use App\Models\WorldCupMatch;
use Illuminate\Database\Eloquent\Collection;

class WorldCupController extends Controller
{
    public function todayMatch(): Collection
    {
        return WorldCupMatch::where('season', '2026')
            ->forTodayWindow()
            ->with([
                'homeTeam:id,name,flag_icon,group,fifa_code',
                'awayTeam:id,name,flag_icon,group,fifa_code',
                'commentaries' => function ($query) {
                    $query->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at')->limit(2);
                },
            ])
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->get()
            ->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);
    }

    /**
     * @return array<string, mixed>
     */
    public function matchDetails(int $id): array
    {
        $match = WorldCupMatch::where('season', '2026')
            ->with([
                'timeLines' => function ($query) {
                    $query->select('news_id', 'title', 'details', 'date', 'image_path', 'image_caption')
                        ->where('is_publish', true)
                        ->orderByDesc('date');
                },
                'homeTeam:id,name,flag_icon,group,fifa_code',
                'awayTeam:id,name,flag_icon,group,fifa_code',
                'commentaries' => function ($query) {
                    $query->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at');
                },
            ])
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->where('id', $id)
            ->first();

        if (! $match) {
            abort(404);
        }

        return [
            'match_data' => $match->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function allMatches(): array
    {
        $matches = WorldCupMatch::where('season', '2026')
            ->with([
                'homeTeam:id,name,flag_icon,group,fifa_code',
                'awayTeam:id,name,flag_icon,group,fifa_code',
                'commentaries' => function ($query) {
                    $query->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at')->limit(2);
                },
            ])
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->get()
            ->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);

        return [
            'match_data' => $matches,
        ];
    }
}
