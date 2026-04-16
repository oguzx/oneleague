<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Tournament;

class PlayAllAction
{
    public function __construct(private readonly PlayMatchAction $playMatch) {}

    /**
     * Play all remaining scheduled fixtures in match-week order.
     *
     * @throws InvalidTournamentStateException
     */
    public function execute(Tournament $tournament): void
    {
        $tournament->loadMissing(['groups.fixtures']);

        $scheduled = $tournament->groups
            ->flatMap->fixtures
            ->filter(fn($f) => $f->status === FixtureStatus::Scheduled)
            ->sortBy('match_week')
            ->values();

        if ($scheduled->isEmpty()) {
            throw new InvalidTournamentStateException('No scheduled fixtures remaining in this tournament.');
        }

        foreach ($scheduled as $fixture) {
            $this->playMatch->execute($fixture);
        }
    }
}
