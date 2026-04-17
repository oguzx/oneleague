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
            ->where('tournament_id', $tournamentId)
            ->where('status', '!=', FixtureStatus::Completed->value)
            ->min('match_week');
    }

    /**
     * Returns true when every fixture in the given week is Completed.
     */
    public function isWeekCompleted(string $tournamentId, int $week): bool
    {
        return !Fixture::query()
            ->where('tournament_id', $tournamentId)
            ->where('match_week', $week)
            ->where('status', '!=', FixtureStatus::Completed->value)
            ->exists();
    }

    /**
     * Returns the first week after $afterWeek that still contains
     * Scheduled fixtures. Returns null if no such week exists.
     */
    public function resolveNextPlayableWeek(string $tournamentId, int $afterWeek): ?int
    {
        return Fixture::query()
            ->where('tournament_id', $tournamentId)
            ->where('match_week', '>', $afterWeek)
            ->where('status', '!=', FixtureStatus::Completed->value)
            ->min('match_week');
    }

    /**
     * Returns true when no non-completed fixtures remain for the tournament.
     */
    public function isTournamentCompleted(string $tournamentId): bool
    {
        return $this->resolveFirstPlayableWeek($tournamentId) === null;
    }

    /**
     * Returns the highest match_week in the tournament (i.e. total weeks).
     */
    public function resolveTotalWeeks(string $tournamentId): int
    {
        return (int) Fixture::query()
            ->where('tournament_id', $tournamentId)
            ->max('match_week');
    }

    /**
     * Returns current and total weeks in a single query — use this on hot
     * poll endpoints instead of calling resolveFirstPlayableWeek + resolveTotalWeeks separately.
     *
     * @return array{current_week: int|null, total_weeks: int}
     */
    public function resolveWeekSummary(string $tournamentId): array
    {
        $row = Fixture::query()
            ->where('tournament_id', $tournamentId)
            ->selectRaw(
                'MIN(CASE WHEN status != ? THEN match_week END) AS current_week,
                 MAX(match_week) AS total_weeks',
                [FixtureStatus::Completed->value]
            )
            ->first();

        return [
            'current_week' => $row?->current_week !== null ? (int) $row->current_week : null,
            'total_weeks'  => (int) ($row?->total_weeks ?? 0),
        ];
    }
}
