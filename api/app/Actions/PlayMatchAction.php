<?php

namespace App\Actions;

use App\Data\SimulationResultData;
use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Fixture;
use App\Services\MatchEventPersistenceService;
use App\Services\Simulation\MatchContextFactory;
use App\Services\Simulation\MatchStateFactory;
use Illuminate\Support\Facades\DB;

class PlayMatchAction
{
    public function __construct(
        private readonly MatchContextFactory          $contextFactory,
        private readonly MatchStateFactory            $stateFactory,
        private readonly SimulateMatchAction          $simulator,
        private readonly MatchEventPersistenceService $eventPersistence,
    ) {}

    /**
     * Validate → simulate → persist result + events.
     * Standings computation is the caller's responsibility.
     *
     * @throws InvalidTournamentStateException
     */
    public function execute(Fixture $fixture): SimulationResultData
    {
        $this->guardPlayable($fixture);

        $fixture->loadMissing(['homeTeam.stat', 'awayTeam.stat']);

        $context = $this->contextFactory->build($fixture);
        $state   = $this->stateFactory->build($context);
        $result  = $this->simulator->execute($context, $state);

        DB::transaction(function () use ($fixture, $result) {
            $this->persistMatchResult($fixture, $result);
            $this->eventPersistence->persist($result);
        });

        return $result;
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
            'home_score' => $result->homeScore,
            'away_score' => $result->awayScore,
            'weather'    => $result->weather->value,
        ]);

        $fixture->transitionTo(FixtureStatus::Completed);
    }
}
