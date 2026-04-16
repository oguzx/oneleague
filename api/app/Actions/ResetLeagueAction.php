<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
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

        DB::transaction(function () use ($fixtureIds) {
            MatchEvent::whereIn('fixture_id', $fixtureIds)->delete();

            Fixture::whereIn('id', $fixtureIds)->update([
                'status'     => FixtureStatus::Scheduled->value,
                'home_score' => null,
                'away_score' => null,
            ]);
        });
    }
}
