<?php

namespace App\Actions;

use App\Data\LeagueTableRowData;
use App\Data\SimulationResultData;
use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Fixture;
use App\Models\MatchEvent;
use App\Services\LeagueTableService;
use App\Services\Simulation\MatchContextFactory;
use App\Services\Simulation\MatchStateFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayMatchAction
{
    public function __construct(
        private readonly MatchContextFactory  $contextFactory,
        private readonly MatchStateFactory    $stateFactory,
        private readonly SimulateMatchAction  $simulator,
        private readonly LeagueTableService   $leagueTable,
    ) {}

    /**
     * Orchestrates the full match play flow:
     * validate → simulate → persist result + events → recalculate standings.
     *
     * @return array{result: SimulationResultData, table: Collection<LeagueTableRowData>}
     * @throws InvalidTournamentStateException
     */
    public function execute(Fixture $fixture): array
    {
        $this->guardPlayable($fixture);

        $fixture->loadMissing(['homeTeam.stat', 'awayTeam.stat', 'group.teams']);

        $context = $this->contextFactory->build($fixture);
        $state   = $this->stateFactory->build($context);
        $result  = $this->simulator->execute($context, $state);

        DB::transaction(function () use ($fixture, $result) {
            $this->persistMatchResult($fixture, $result);
            $this->persistMatchEvents($result);
        });

        $table = $this->leagueTable->forGroup($fixture->group);

        return ['result' => $result, 'table' => $table];
    }

    private function guardPlayable(Fixture $fixture): void
    {
        if (!$fixture->isPlayable()) {
            throw new InvalidTournamentStateException(
                "Fixture {$fixture->id} cannot be played (status: {$fixture->status->value})."
            );
        }

        if ($fixture->home_team_id === $fixture->away_team_id) {
            throw new InvalidTournamentStateException('Home and away team cannot be the same.');
        }
    }

    private function persistMatchResult(Fixture $fixture, SimulationResultData $result): void
    {
        $fixture->update([
            'status'     => FixtureStatus::Completed,
            'home_score' => $result->homeScore,
            'away_score' => $result->awayScore,
        ]);
    }

    private function persistMatchEvents(SimulationResultData $result): void
    {
        $now      = now();
        $sequence = 0;
        $rows     = [];

        foreach ($result->events as $event) {
            $rows[] = [
                'id'               => (string) Str::uuid(),
                'fixture_id'       => $result->fixtureId,
                'minute'           => $event->minute,
                'second'           => $event->second,
                'tick_number'      => $event->tick,
                'sequence'         => $sequence++,
                'team_id'          => $event->teamId,
                'opponent_team_id' => $event->opponentTeamId,
                'event_type'       => $event->type->value,
                'zone'             => $event->zone->value,
                'payload'          => json_encode($event->payload),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        if (!empty($rows)) {
            MatchEvent::insert($rows);
        }
    }
}
