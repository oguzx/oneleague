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
        private readonly GroupService $groupService,
        private readonly FixtureService $fixtureService,
    ) {}

    public function draw(): Tournament
    {
        $teams = Team::with('stat')->get();

        $pots = $this->buildValidatedPots($teams);
        $shuffledPots = $this->shufflePots($pots);

        $groupAssignments = $this->buildGroupAssignments($shuffledPots);

        return $this->persistTournament($groupAssignments);
    }

    private function buildValidatedPots(Collection $teams): Collection
    {
        $this->validateTeams($teams);

        return $teams
            ->groupBy(fn (Team $team) => $team->stat->pot)
            ->sortKeys();
    }

    private function shufflePots(Collection $pots): Collection
    {
        return $pots->map(function (Collection $pot) {
            $teams = $pot->values()->all();
            shuffle($teams);
            return $teams;
        });
    }

    private function buildGroupAssignments(Collection $pots): array
    {
        $groupCount = count($pots->first());

        $groupCountries = array_fill(0, $groupCount, []);
        $groupTeams     = array_fill(0, $groupCount, []);

        foreach ($pots as $potTeams) {
            $assigned = $this->assignPot($potTeams, $groupCountries);

            foreach ($assigned as $groupIndex => $team) {
                $groupTeams[$groupIndex][] = $team->id;
            }
        }

        return $groupTeams;
    }

    private function assignPot(array $teams, array &$groupCountries): array
    {
        $assignment = array_fill(0, count($teams), null);

        if (! $this->backtrackAssign($teams, 0, $assignment, $groupCountries)) {
            throw new InvalidTournamentStateException(
                'Draw failed: country constraint cannot be satisfied.'
            );
        }

        return $assignment;
    }

    private function backtrackAssign(
        array $teams,
        int $index,
        array &$assignment,
        array &$groupCountries,
    ): bool {
        if ($index === count($teams)) {
            return true;
        }

        $team = $teams[$index];
        $groupIndexes = range(0, count($teams) - 1);

        shuffle($groupIndexes);

        foreach ($groupIndexes as $groupIndex) {
            if (! $this->canPlaceTeam($team, $groupIndex, $assignment, $groupCountries)) {
                continue;
            }

            $this->placeTeam($team, $groupIndex, $assignment, $groupCountries);

            if ($this->backtrackAssign($teams, $index + 1, $assignment, $groupCountries)) {
                return true;
            }

            $this->removeTeam($groupIndex, $assignment, $groupCountries);
        }

        return false;
    }

    private function canPlaceTeam(
        Team $team,
        int $groupIndex,
        array $assignment,
        array $groupCountries
    ): bool {
        if ($assignment[$groupIndex] !== null) {
            return false;
        }

        return ! in_array($team->country_code, $groupCountries[$groupIndex], true);
    }

    private function placeTeam(
        Team $team,
        int $groupIndex,
        array &$assignment,
        array &$groupCountries
    ): void {
        $assignment[$groupIndex] = $team;
        $groupCountries[$groupIndex][] = $team->country_code;
    }

    private function removeTeam(
        int $groupIndex,
        array &$assignment,
        array &$groupCountries
    ): void {
        $assignment[$groupIndex] = null;
        array_pop($groupCountries[$groupIndex]);
    }

    private function persistTournament(array $groupTeams): Tournament
    {
        return DB::transaction(function () use ($groupTeams) {
            $tournament = Tournament::create([
                'name' => 'Champions League',
            ]);

            foreach ($groupTeams as $index => $teamIds) {
                $groupName = chr(65 + $index);

                $group = $this->groupService->createGroup($tournament, $groupName);

                $group->teams()->attach($teamIds);
                $group->load('teams');

                $this->fixtureService->generateForGroup($group);
            }

            return $tournament->load('groups.teams.stat', 'groups.fixtures');
        });
    }

    private function validateTeams(Collection $teams): void
    {
        if ($teams->isEmpty()) {
            throw new InvalidTournamentStateException('No teams found.');
        }

        $missingStats = $teams->filter(fn (Team $t) => $t->stat === null);

        if ($missingStats->isNotEmpty()) {
            $names = $missingStats->pluck('name')->join(', ');
            throw new InvalidTournamentStateException("Teams missing stats: {$names}");
        }

        $pots = $teams->groupBy(fn (Team $t) => $t->stat->pot);

        if ($pots->count() !== self::TEAMS_PER_GROUP) {
            throw new InvalidTournamentStateException('Exactly 4 pots are required.');
        }

        $this->validatePotSizes($pots);
        $this->validatePotSequence($pots);
    }

    private function validatePotSizes(Collection $pots): void
    {
        $sizes = $pots->map(fn (Collection $pot) => $pot->count())->unique();

        if ($sizes->count() > 1) {
            throw new InvalidTournamentStateException('All pots must have equal team counts.');
        }
    }

    private function validatePotSequence(Collection $pots): void
    {
        $potNumbers = $pots->keys()->map(fn ($k) => (int) $k)->sort()->values();
        $expected = range(1, self::TEAMS_PER_GROUP);

        if ($potNumbers->all() !== $expected) {
            throw new InvalidTournamentStateException('Pot numbers must be consecutive.');
        }
    }
}
