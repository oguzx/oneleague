<?php

namespace App\Services;

use App\Data\SimulationResultData;
use App\Models\MatchEvent;
use Illuminate\Support\Str;

/**
 * Persists the visible timeline events produced by a simulation run.
 * Isolated from PlayMatchAction so event-row construction logic
 * has a single, testable home.
 */
class MatchEventPersistenceService
{
    public function persist(SimulationResultData $result): void
    {
        if (empty($result->events)) {
            return;
        }

        $now      = now();
        $sequence = 0;
        $rows     = [];

        foreach ($result->events as $event) {
            $rows[] = [
                'id'               => (string) Str::uuid(),
                'fixture_id'       => $result->fixtureId,
                'minute'           => $event->minute,
                'second'           => $event->second,
                'tick_number'      => $event->tick,
                'sequence'         => $sequence++,
                'team_id'          => $event->teamId,
                'opponent_team_id' => $event->opponentTeamId,
                'event_type'       => $event->type->value,
                'zone'             => $event->zone->value,
                'payload'          => json_encode($event->payload),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        MatchEvent::insert($rows);
    }
}
