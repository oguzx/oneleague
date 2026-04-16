<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidTournamentStateException;
use App\Models\Tournament;
use App\Services\DrawService;
use Illuminate\Http\JsonResponse;

class DrawController extends Controller
{
    public function __construct(private readonly DrawService $drawService) {}

    public function __invoke(): JsonResponse
    {
        try {
            $tournament = $this->drawService->draw();
        } catch (InvalidTournamentStateException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatResponse($tournament), 201);
    }

    private function formatResponse(Tournament $tournament): array
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
