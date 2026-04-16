<?php

namespace App\Services\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;

/**
 * Adjusts weights based on the current match phase (set-piece context).
 *
 * Runs first in the pipeline so that contextual setup is established
 * before team-stat and physical modifiers apply on top of it.
 *
 * Uses state->phase rather than state->lastEventType because phase is
 * explicitly maintained by EventApplier and is more reliable:
 *   - CornerWon  sets phase = CornerKick
 *   - FoulCommitted sets phase = FreeKick
 *   - possession/goal/half-time events reset phase = Normal
 *
 * All adjustments are relative (scale), never absolute overwrites.
 */
class LastEventContextWeightModifier implements EventWeightModifierInterface
{
    public function modify(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void {
        match($state->phase) {
            MatchPhase::CornerKick => $this->applyCornerKickContext($bag),
            MatchPhase::FreeKick   => $this->applyFreeKickContext($bag),
            default                => null,
        };
    }

    /**
     * Corners favour direct shots and physical aerial duels.
     * Passing is slightly deprioritised as the cross is already in the air.
     */
    private function applyCornerKickContext(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::ShotAttempt,   1.60);
        $bag->scale(MatchEventType::FoulCommitted, 1.30);
        $bag->scale(MatchEventType::PassCompleted, 0.80);
    }

    /**
     * Free kicks create direct shooting danger and structured passing moves.
     * Defensive errors (fouls in the wall, rash challenges) decrease.
     */
    private function applyFreeKickContext(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::ShotAttempt,   1.40);
        $bag->scale(MatchEventType::PassCompleted, 1.20);
        $bag->scale(MatchEventType::FoulCommitted, 0.70);
    }
}
