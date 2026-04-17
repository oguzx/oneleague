<?php

namespace App\Services;

use App\Exceptions\InvalidTournamentStateException;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Validates that the team pool satisfies all structural requirements for a draw.
 * Single responsibility: throw InvalidTournamentStateException on any violation.
 */
class DrawValidator
{
    public function validate(Collection $teams): void
    {
        $this->assertTeamsExist($teams);
        $this->assertAllHaveStats($teams);

        $pots = $teams->groupBy(fn(Team $t) => $t->stat->pot);

        $this->assertPotCount($pots);
        $this->assertEqualPotSizes($pots);
        $this->assertConsecutivePotNumbers($pots);
    }

    private function assertTeamsExist(Collection $teams): void
    {
        if ($teams->isEmpty()) {
            throw new InvalidTournamentStateException('No teams found.');
        }
    }

    private function assertAllHaveStats(Collection $teams): void
    {
        $missing = $teams->filter(fn(Team $t) => $t->stat === null);

        if ($missing->isNotEmpty()) {
            $names = $missing->pluck('name')->join(', ');
            throw new InvalidTournamentStateException("Teams missing stats: {$names}");
        }
    }

    private function assertPotCount(Collection $pots): void
    {
        $required = config('league.teams_per_group');

        if ($pots->count() !== $required) {
            throw new InvalidTournamentStateException("Exactly {$required} pots are required.");
        }
    }

    private function assertEqualPotSizes(Collection $pots): void
    {
        $sizes = $pots->map(fn(Collection $pot) => $pot->count())->unique();

        if ($sizes->count() > 1) {
            throw new InvalidTournamentStateException('All pots must have equal team counts.');
        }
    }

    private function assertConsecutivePotNumbers(Collection $pots): void
    {
        $required   = config('league.teams_per_group');
        $potNumbers = $pots->keys()->map(fn($k) => (int) $k)->sort()->values();
        $expected   = range(1, $required);

        if ($potNumbers->all() !== $expected) {
            throw new InvalidTournamentStateException('Pot numbers must be consecutive.');
        }
    }
}
