<?php

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Models\Fixture;

/**
 * Pure query service — resolves week progression from the fixtures table.
 * Never writes. Week state is always derived from fixture truth.
 */
class TournamentWeekResolver
{
    /**
     * Returns the minimum match_week that still contains at least one
     * non-completed fixture for the given tournament.
     * Returns null when all fixtures are completed (tournament is done).
     */
    public function resolveFirstPlayableWeek(string $tournamentId): ?int
    {
        return Fixture::query()
            ->join('groups', 'groups.id', '=', 'fixtures.group_id')
            ->where('groups.tournament_id', $tournamentId)
            ->where('fixtures.status', '!=', FixtureStatus::Completed->value)
            ->min('fixtures.match_week');
    }

    /**
     * Returns true when every fixture in the given week is Completed.
     */
    public function isWeekCompleted(string $tournamentId, int $week): bool
    {
        return !Fixture::query()
            ->join('groups', 'groups.id', '=', 'fixtures.group_id')
            ->where('groups.tournament_id', $tournamentId)
            ->where('fixtures.match_week', $week)
            ->where('fixtures.status', '!=', FixtureStatus::Completed->value)
            ->exists();
    }

    /**
     * Returns the first week after $afterWeek that still contains
     * Scheduled fixtures. Returns null if no such week exists.
     */
    public function resolveNextPlayableWeek(string $tournamentId, int $afterWeek): ?int
    {
        return Fixture::query()
            ->join('groups', 'groups.id', '=', 'fixtures.group_id')
            ->where('groups.tournament_id', $tournamentId)
            ->where('fixtures.match_week', '>', $afterWeek)
            ->where('fixtures.status', '!=', FixtureStatus::Completed->value)
            ->min('fixtures.match_week');
    }

    /**
     * Returns true when no non-completed fixtures remain for the tournament.
     */
    public function isTournamentCompleted(string $tournamentId): bool
    {
        return $this->resolveFirstPlayableWeek($tournamentId) === null;
    }
}
