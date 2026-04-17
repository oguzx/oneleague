<?php

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FixtureService
{
    /**
     * Generate a double round-robin schedule for a group and persist it.
     * Uses the circle (polygon) rotation method.
     */
    public function generateForGroup(Group $group): void
    {
        $teams     = $group->teams->values()->all();
        $teamCount = count($teams);
        $required  = config('league.teams_per_group');

        if ($teamCount !== $required) {
            throw new InvalidTournamentStateException(
                "Each group must have exactly {$required} teams, got {$teamCount} in group '{$group->name}'."
            );
        }

        $schedule = $this->buildSchedule($teams);
        $now = now();

        Fixture::insert(
            $schedule->map(fn($f) => [
                'id'              => (string) Str::uuid(),
                'tournament_id'   => $group->tournament_id,
                'group_id'        => $group->id,
                'home_team_id'    => $f['home']->id,
                'away_team_id'    => $f['away']->id,
                'match_week'      => $f['week'],
                'status'          => FixtureStatus::Scheduled->value,
                'created_at'      => $now,
                'updated_at'      => $now,
            ])->all()
        );
    }

    /**
     * Build a 12-fixture schedule (6 weeks) without touching the database.
     * Returned as a Collection of ['home' => Team, 'away' => Team, 'week' => int].
     *
     * @param  Team[]  $teams
     */
    public function buildSchedule(array $teams): Collection
    {
        $n = count($teams);         // 4
        $rounds = $n - 1;           // 3 rounds per leg
        $fixed = $teams[0];
        $rotating = array_slice($teams, 1); // [B, C, D]

        $firstLeg = collect();

        for ($round = 0; $round < $rounds; $round++) {
            $circle = array_merge([$fixed], $rotating);
            $week = $round + 1;

            for ($i = 0; $i < intdiv($n, 2); $i++) {
                $firstLeg->push([
                    'home' => $circle[$i],
                    'away' => $circle[$n - 1 - $i],
                    'week' => $week,
                ]);
            }

            // Circle rotation: move last element to front
            array_unshift($rotating, array_pop($rotating));
        }

        // Second leg: swap home/away, offset weeks by number of rounds
        $secondLeg = $firstLeg->map(fn($f) => [
            'home' => $f['away'],
            'away' => $f['home'],
            'week' => $f['week'] + $rounds,
        ]);

        return $firstLeg->concat($secondLeg);
    }
}
