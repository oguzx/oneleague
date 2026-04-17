<?php

namespace App\Services;

use App\Data\LeagueTableRowData;
use App\Enums\FixtureStatus;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeagueTableService
{
    /**
     * Derive standings for a group from completed fixtures only.
     * Sorted by: points desc → goal difference desc → goals for desc.
     *
     * @return Collection<LeagueTableRowData>
     */
    public function forGroup(Group $group): Collection
    {
        $group->loadMissing(['teams', 'fixtures']);

        $lastUpdated = $group->fixtures->max('updated_at')?->timestamp ?? 0;
        $cacheKey    = "standings:{$group->id}:{$lastUpdated}";

        $rows = Cache::remember($cacheKey, 300, fn() =>
            $this->compute($group)->map(fn(LeagueTableRowData $r) => [
                'teamId'         => $r->teamId,
                'teamName'       => $r->teamName,
                'logoUrl'        => $r->logoUrl,
                'played'         => $r->played,
                'won'            => $r->won,
                'drawn'          => $r->drawn,
                'lost'           => $r->lost,
                'goalsFor'       => $r->goalsFor,
                'goalsAgainst'   => $r->goalsAgainst,
                'goalDifference' => $r->goalDifference,
                'points'         => $r->points,
            ])->all()
        );

        return collect($rows)->map(fn($r) => new LeagueTableRowData(
            teamId:         $r['teamId'],
            teamName:       $r['teamName'],
            logoUrl:        $r['logoUrl'],
            played:         $r['played'],
            won:            $r['won'],
            drawn:          $r['drawn'],
            lost:           $r['lost'],
            goalsFor:       $r['goalsFor'],
            goalsAgainst:   $r['goalsAgainst'],
            goalDifference: $r['goalDifference'],
            points:         $r['points'],
        ));
    }

    private function compute(Group $group): Collection
    {
        // Use a plain PHP array for mutation — Collection::offsetGet returns a copy,
        // so in-place increments on a Collection have no effect.
        $rows = $group->teams->mapWithKeys(fn($team) => [
            $team->id => [
                'team'          => $team,
                'played'        => 0,
                'won'           => 0,
                'drawn'         => 0,
                'lost'          => 0,
                'goals_for'     => 0,
                'goals_against' => 0,
            ],
        ])->all();

        foreach ($group->fixtures->where('status', FixtureStatus::Completed) as $fixture) {
            $this->applyFixture($rows, $fixture);
        }

        return collect($rows)
            ->map(fn($r) => $this->toRowData($r))
            ->sortByDesc(fn(LeagueTableRowData $r) => [$r->points, $r->goalDifference, $r->goalsFor])
            ->values();
    }

    private function applyFixture(array &$rows, $fixture): void
    {
        $homeId = $fixture->home_team_id;
        $awayId = $fixture->away_team_id;
        $hg     = $fixture->home_score;
        $ag     = $fixture->away_score;

        if (!isset($rows[$homeId], $rows[$awayId])) {
            return;
        }

        $rows[$homeId]['played']++;
        $rows[$awayId]['played']++;
        $rows[$homeId]['goals_for']     += $hg;
        $rows[$homeId]['goals_against'] += $ag;
        $rows[$awayId]['goals_for']     += $ag;
        $rows[$awayId]['goals_against'] += $hg;

        if ($hg > $ag) {
            $rows[$homeId]['won']++;
            $rows[$awayId]['lost']++;
        } elseif ($ag > $hg) {
            $rows[$awayId]['won']++;
            $rows[$homeId]['lost']++;
        } else {
            $rows[$homeId]['drawn']++;
            $rows[$awayId]['drawn']++;
        }
    }

    private function toRowData(array $r): LeagueTableRowData
    {
        $gd = $r['goals_for'] - $r['goals_against'];
        $pts = $r['won'] * 3 + $r['drawn'];

        return new LeagueTableRowData(
            teamId:         $r['team']->id,
            teamName:       $r['team']->name,
            logoUrl:        $r['team']->logo_url,
            played:         $r['played'],
            won:            $r['won'],
            drawn:          $r['drawn'],
            lost:           $r['lost'],
            goalsFor:       $r['goals_for'],
            goalsAgainst:   $r['goals_against'],
            goalDifference: $gd,
            points:         $pts,
        );
    }
}
