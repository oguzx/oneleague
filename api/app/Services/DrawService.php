<?php

namespace App\Services;

use App\Exceptions\InvalidTournamentStateException;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawService
{
    private const TEAMS_PER_GROUP = 4;

    public function __construct(
        private readonly GroupService   $groupService,
        private readonly FixtureService $fixtureService,
    ) {}

    /**
     * Run a full tournament draw:
     * validate → shuffle pots → create groups → assign teams → generate fixtures.
     * The entire operation is wrapped in a single database transaction.
     *
     * @throws InvalidTournamentStateException
     */
    public function draw(): Tournament
    {
        $teams = Team::with('stat')->get();

        $pots = self::validateAndGroup($teams);

        $shuffledPots = $pots->map(function (Collection $pot): array {
            $arr = $pot->values()->all();
            shuffle($arr);
            return $arr;
        });

        $groupCount = count($shuffledPots->first());

        return DB::transaction(function () use ($shuffledPots, $groupCount): Tournament {
            $tournament = Tournament::create(['name' => 'Champions League']);

            for ($i = 0; $i < $groupCount; $i++) {
                $groupName = chr(65 + $i); // A, B, C, …
                $group = $this->groupService->createGroup($tournament, $groupName);

                $teamIds = $shuffledPots->map(fn(array $pot) => $pot[$i]->id)->all();
                $group->teams()->attach($teamIds);

                $group->load('teams');
                $this->fixtureService->generateForGroup($group);
            }

            return $tournament->load('groups.teams.stat', 'groups.fixtures');
        });
    }

    /**
     * Validate the team set and return teams grouped by pot (sorted by pot number).
     *
     * Rules enforced:
     * - All teams must have a stat record.
     * - Pots must be numbered 1..N consecutively with no gaps.
     * - Every pot must contain the same number of teams.
     * - Pot count must equal TEAMS_PER_GROUP (4) so each group gets one team per pot.
     *
     * @throws InvalidTournamentStateException
     */
    private static function validateAndGroup(Collection $teams): Collection
    {
        if ($teams->isEmpty()) {
            throw new InvalidTournamentStateException('No teams found in the database.');
        }

        $missing = $teams->filter(fn(Team $t) => $t->stat === null);
        if ($missing->isNotEmpty()) {
            $names = $missing->pluck('name')->join(', ');
            throw new InvalidTournamentStateException("Teams missing stat records: {$names}.");
        }

        $pots = $teams->groupBy(fn(Team $t) => $t->stat->pot)->sortKeys();

        if ($pots->count() !== self::TEAMS_PER_GROUP) {
            throw new InvalidTournamentStateException(
                sprintf(
                    'Exactly %d pots are required (one per team per group). Found %d.',
                    self::TEAMS_PER_GROUP,
                    $pots->count()
                )
            );
        }

        $sizes = $pots->map(fn(Collection $p) => $p->count())->unique();
        if ($sizes->count() > 1) {
            $breakdown = $pots->map(fn(Collection $p, int $k) => "Pot {$k}: {$p->count()}")->join(', ');
            throw new InvalidTournamentStateException(
                "All pots must have equal team counts. Found: {$breakdown}."
            );
        }

        return $pots;
    }
}
