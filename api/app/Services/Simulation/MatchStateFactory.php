<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Enums\MatchEventType;

class MatchStateFactory
{
    public function build(MatchContextData $context): MatchStateData
    {
        $state = new MatchStateData($context->homeTeamId, $context->awayTeamId);
        $state->setLastEvent(MatchEventType::Kickoff);

        return $state;
    }
}
