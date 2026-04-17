<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Fixture;
use App\Models\Tournament;
use App\Services\TournamentWeekResolver;

class PlayWeekAction
{
    public function __construct(
        private readonly PlayMatchAction      $playMatch,
        private readonly TournamentWeekResolver $resolver,
    ) {}

    /**
     * Play all scheduled fixtures for the earliest remaining match week.
     *
     * @return int The match week that was played.
     * @throws InvalidTournamentStateException
     */
    public function execute(Tournament $tournament): int
    {
        $currentWeek = $this->resolver->resolveFirstPlayableWeek($tournament->id);

        if ($currentWeek === null) {
            throw new InvalidTournamentStateException('No scheduled fixtures remaining in this tournament.');
        }

        $weekFixtures = Fixture::with(['homeTeam.stat', 'awayTeam.stat'])
            ->join('groups', 'groups.id', '=', 'fixtures.group_id')
            ->where('groups.tournament_id', $tournament->id)
            ->where('fixtures.match_week', $currentWeek)
            ->where('fixtures.status', FixtureStatus::Scheduled->value)
            ->select('fixtures.*')
            ->get();

        foreach ($weekFixtures as $fixture) {
            $this->playMatch->execute($fixture);
        }

        return $currentWeek;
    }
}
