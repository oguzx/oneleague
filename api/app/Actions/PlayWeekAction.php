<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Tournament;

class PlayWeekAction
{
    public function __construct(private readonly PlayMatchAction $playMatch) {}

    /**
     * Play all scheduled fixtures for the earliest remaining match week.
     *
     * @return int The match week that was played.
     * @throws InvalidTournamentStateException
     */
    public function execute(Tournament $tournament): int
    {
        $tournament->loadMissing(['groups.fixtures']);

        $scheduled = $tournament->groups
            ->flatMap->fixtures
            ->filter(fn($f) => $f->status === FixtureStatus::Scheduled);

        $currentWeek = $scheduled->min('match_week');

        if ($currentWeek === null) {
            throw new InvalidTournamentStateException('No scheduled fixtures remaining in this tournament.');
        }

        $weekFixtures = $scheduled->filter(fn($f) => $f->match_week === $currentWeek);

        foreach ($weekFixtures as $fixture) {
            $this->playMatch->execute($fixture);
        }

        return $currentWeek;
    }
}
