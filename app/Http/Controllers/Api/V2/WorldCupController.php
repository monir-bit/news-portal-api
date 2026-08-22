<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\WorldCupMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WorldCupController extends Controller
{
    public function todayMatch(): Collection
    {
        return $this->baseMatchesQuery()
            ->forTodayWindow()
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->get()
            ->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);
    }

    /**
     * @return array{match_data: mixed}
     */
    public function matchDetails(string $id): array
    {
        $match = WorldCupMatch::where('season', '2026')
            ->with([
                'timeLines' => function (Builder $query): void {
                    $query->select('news_id', 'title', 'details', 'date', 'image_path', 'image_caption')
                        ->where('is_publish', true)
                        ->orderByDesc('date');
                },
                'homeTeam:id,name,flag_icon,group,fifa_code',
                'awayTeam:id,name,flag_icon,group,fifa_code',
                'commentaries' => function (Builder $query): void {
                    $query->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at');
                },
            ])
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->where('id', $id)
            ->first()
            ->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);

        return [
            'match_data' => $match,
        ];
    }

    /**
     * @return array{match_data: Collection}
     */
    public function allMatches(): array
    {
        $matches = $this->baseMatchesQuery()
            ->orderBy('match_date')
            ->orderBy('start_time')
            ->get()
            ->makeHidden(['team_a', 'team_b', 'created_at', 'updated_at', 'season']);

        return [
            'match_data' => $matches,
        ];
    }

    private function baseMatchesQuery(): Builder
    {
        return WorldCupMatch::where('season', '2026')
            ->with([
                'homeTeam:id,name,flag_icon,group,fifa_code',
                'awayTeam:id,name,flag_icon,group,fifa_code',
                'commentaries' => function (Builder $query): void {
                    $query->orderByDesc('created_at')->select('id', 'match_id', 'description', 'created_at')->limit(2);
                },
            ]);
    }
}
