<?php

namespace App\Services;

use App\Models\Tournament;

/**
 * Formats a freshly drawn tournament into the draw-result API response shape.
 * Distinct from TournamentFormatter which produces the full live-state view.
 */
class DrawFormatter
{
    public function format(Tournament $tournament): array
    {
        return [
            'tournament_id' => $tournament->id,
            'groups'        => $tournament->groups->map(fn($group) => [
                'name'  => $group->name,
                'teams' => $group->teams->map(fn($team) => [
                    'id'           => $team->id,
                    'name'         => $team->name,
                    'country_code' => $team->country_code,
                    'pot'          => $team->stat?->pot,
                ]),
                'fixtures' => $group->fixtures
                    ->groupBy('match_week')
                    ->sortKeys()
                    ->map(fn($weekFixtures) => $weekFixtures->map(fn($f) => [
                        'home_team_id' => $f->home_team_id,
                        'away_team_id' => $f->away_team_id,
                    ])),
            ]),
        ];
    }
}
