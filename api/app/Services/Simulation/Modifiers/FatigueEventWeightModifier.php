<?php

namespace App\Services\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Enums\MatchEventType;

/**
 * Increases error and foul rates as the possessing team tires.
 *
 * Fatigue is a 0–1 value accumulated during the match.
 * As it rises, the team loses the ball more often and commits more fouls.
 * Applied after stat modifiers so fatigue degrades the ability baseline,
 * not the other way around.
 */
class FatigueEventWeightModifier implements EventWeightModifierInterface
{
    public function modify(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void {
        $fatigue = $state->possessionFatigue();

        $bag->scale(MatchEventType::PassFailed,    1.0 + $fatigue * 0.40);
        $bag->scale(MatchEventType::DribbleFailed, 1.0 + $fatigue * 0.30);
        $bag->scale(MatchEventType::FoulCommitted, 1.0 + $fatigue * 0.25);
    }
}
