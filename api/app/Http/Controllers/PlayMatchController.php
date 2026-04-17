<?php

namespace App\Http\Controllers;

use App\Actions\PlayMatchAction;
use App\Data\LeagueTableRowData;
use App\Data\MatchEventData;
use App\Data\SimulationResultData;
use App\Exceptions\InvalidTournamentStateException;
use App\Http\Responses\ApiResponse;
use App\Models\Fixture;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PlayMatchController extends Controller
{
    public function __construct(private readonly PlayMatchAction $action) {}

    public function __invoke(Fixture $fixture): JsonResponse
    {
        try {
            ['result' => $result, 'table' => $table] = $this->action->execute($fixture);
        } catch (InvalidTournamentStateException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'INVALID_STATE'
            );
        }

        return ApiResponse::success($this->formatResponse($result, $table));
    }

    private function formatResponse(SimulationResultData $result, $table): array
    {
        return [
            'fixture_id' => $result->fixtureId,
            'score'      => [
                'home' => $result->homeScore,
                'away' => $result->awayScore,
            ],
            'timeline' => collect($result->events)->map(fn(MatchEventData $e) => [
                'minute'     => $e->minute,
                'second'     => $e->second,
                'event'      => $e->type->value,
                'team_id'    => $e->teamId,
                'zone'       => $e->zone->value,
                'payload'    => $e->payload,
            ]),
            'standings' => $table->map(fn(LeagueTableRowData $r) => [
                'team'             => $r->teamName,
                'logo_url'         => $r->logoUrl,
                'played'           => $r->played,
                'won'              => $r->won,
                'drawn'            => $r->drawn,
                'lost'             => $r->lost,
                'goals_for'        => $r->goalsFor,
                'goals_against'    => $r->goalsAgainst,
                'goal_difference'  => $r->goalDifference,
                'points'           => $r->points,
            ]),
        ];
    }
}
