<?php

namespace App\Services\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Enums\MatchEventType;
use App\Services\Simulation\MatchConstants;

/**
 * Amplifies positive-play events when the possessing team is on a momentum wave,
 * and softens them when momentum is low.
 *
 * Momentum is 0–1; the neutral point (0.5) produces no change.
 * Applied last in the pipeline so it sits on top of all structural adjustments.
 */
class MomentumEventWeightModifier implements EventWeightModifierInterface
{
    public function modify(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void {
        $momentum = $state->possessionMomentum();
        $boost    = 1.0 + ($momentum - MatchConstants::MOMENTUM_NEUTRAL) * 0.20;

        $bag->scale(MatchEventType::ShotAttempt,    $boost);
        $bag->scale(MatchEventType::PassCompleted,  $boost);
        $bag->scale(MatchEventType::DribbleSuccess, $boost);
    }
}
