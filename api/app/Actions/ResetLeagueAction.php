<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Enums\SimulationStatus;
use App\Models\Fixture;
use App\Models\MatchEvent;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

class ResetLeagueAction
{
    /**
     * Delete all match events and reset every fixture to Scheduled.
     */
    public function execute(Tournament $tournament): void
    {
        $tournament->loadMissing(['groups.fixtures']);

        $fixtureIds = $tournament->groups
            ->flatMap->fixtures
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($tournament, $fixtureIds) {
            MatchEvent::whereIn('fixture_id', $fixtureIds)->delete();

            Fixture::whereIn('id', $fixtureIds)->update([
                'status'     => FixtureStatus::Scheduled->value,
                'home_score' => null,
                'away_score' => null,
            ]);

            $tournament->update([
                'simulation_status'      => SimulationStatus::Idle,
                'simulation_batch_id'    => null,
                'simulation_started_at'  => null,
                'simulation_finished_at' => null,
            ]);
        });
    }
}
