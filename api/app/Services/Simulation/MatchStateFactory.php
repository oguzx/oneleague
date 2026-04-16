<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;

class MatchStateFactory
{
    public function build(MatchContextData $context): MatchStateData
    {
        $state = new MatchStateData();

        $state->homeTeamId      = $context->homeTeamId;
        $state->awayTeamId      = $context->awayTeamId;
        $state->possessionTeamId = $context->homeTeamId; // home team kicks off
        $state->defendingTeamId  = $context->awayTeamId;
        $state->zone             = PitchZone::MiddleThird;
        $state->phase            = MatchPhase::Normal;
        $state->lastEventType    = MatchEventType::Kickoff;

        return $state;
    }
}
