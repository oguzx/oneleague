<?php

namespace App\Services\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;

/**
 * Adjusts weights based on the relative strengths of the two teams.
 *
 * All multipliers are produced by bounded helper methods to keep the weight
 * distribution stable regardless of extreme stat values. No raw arithmetic
 * is written directly inside modify() — helpers make intent explicit.
 *
 * Bounded ranges:
 *   matchupFactor  → [0.70, 1.30]  (attack vs defence comparison)
 *   inverseFactor  → [0.70, 1.00]  (higher stat = lower event chance)
 *   singleStatFactor → [0.85, 1.45]  (higher stat = higher event chance)
 */
class StatEventWeightModifier implements EventWeightModifierInterface
{
    public function modify(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void {
        $possession = $state->possessionIsHome() ? $context->homeProfile : $context->awayProfile;
        $defending  = $state->possessionIsHome() ? $context->awayProfile : $context->homeProfile;

        // Stronger attack vs weaker defence → more shot attempts
        $bag->scale(MatchEventType::ShotAttempt,
            $this->matchupFactor($possession->attack, $defending->defense));

        // Better midfield reduces sloppy turnovers
        $bag->scale(MatchEventType::PassFailed,
            $this->inverseFactor($possession->midfield));

        // Better defensive pressing wins more interceptions
        $bag->scale(MatchEventType::Interception,
            $this->singleStatFactor($defending->pressing));

        // Stronger defensive structure wins more tackles
        $bag->scale(MatchEventType::TackleWon,
            $this->singleStatFactor($defending->defense));

        // Home side gets a modest possession-quality uplift on their own ground
        if ($possession->teamId === $context->homeTeamId) {
            $bag->scale(MatchEventType::PassCompleted, 1.0 + $context->homeAdvantageFactor * 0.10);
            $bag->scale(MatchEventType::ShotAttempt,   1.0 + $context->homeAdvantageFactor * 0.08);
        }
    }

    // ─── Bounded factor helpers ───────────────────────────────────────────────

    /**
     * Compares two 0–1 stats and returns a factor in [0.70, 1.30].
     * Positive difference (attack > defence) boosts the event; negative reduces it.
     */
    private function matchupFactor(float $attack, float $defense): float
    {
        return max(0.70, min(1.30, 1.0 + ($attack - $defense) * 0.60));
    }

    /**
     * Higher stat → lower factor (e.g. good midfield → fewer failed passes).
     * Returns a factor in [0.70, 1.00].
     */
    private function inverseFactor(float $stat): float
    {
        return max(0.70, 1.0 - $stat * 0.30);
    }

    /**
     * Higher stat → higher factor (e.g. strong presser → more interceptions).
     * Returns a factor in [0.85, 1.45].
     */
    private function singleStatFactor(float $stat): float
    {
        return 0.85 + $stat * 0.60;
    }
}
