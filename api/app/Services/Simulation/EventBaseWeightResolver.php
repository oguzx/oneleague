<?php

namespace App\Services\Simulation;

use App\Data\EventWeightBag;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;

/**
 * Assigns raw base weights for a given candidate set.
 *
 * Weights are zone- and phase-relative raw scores that reflect general play
 * tendencies in that area of the pitch or set-piece context.
 *
 * This class knows nothing about team stats, fatigue, or momentum.
 * Modifiers apply those adjustments downstream.
 *
 * Any candidate not explicitly listed in the weight table gets a minimal
 * fallback weight (0.01) so it remains selectable but very unlikely.
 */
class EventBaseWeightResolver
{
    private const FALLBACK_WEIGHT = 0.01;

    /**
     * @param  MatchEventType[] $candidates
     */
    public function resolve(array $candidates, PitchZone $zone, MatchPhase $phase): EventWeightBag
    {
        $table   = $this->weightTable($zone, $phase);
        $weights = [];

        foreach ($candidates as $event) {
            $weights[$event->value] = $table[$event->value] ?? self::FALLBACK_WEIGHT;
        }

        return new EventWeightBag($weights);
    }

    // ─── Weight tables ────────────────────────────────────────────────────────

    /** @return array<string, float> */
    private function weightTable(PitchZone $zone, MatchPhase $phase): array
    {
        if ($phase === MatchPhase::CornerKick) {
            return $this->cornerKickWeights();
        }

        if ($phase === MatchPhase::FreeKick) {
            return $this->freeKickWeights();
        }

        return $this->openPlayWeights($zone);
    }

    /** @return array<string, float> */
    private function openPlayWeights(PitchZone $zone): array
    {
        return match($zone) {
            PitchZone::DefensiveThird => [
                MatchEventType::PassCompleted->value  => 0.55,
                MatchEventType::PassFailed->value     => 0.12,
                MatchEventType::DribbleSuccess->value => 0.09,
                MatchEventType::DribbleFailed->value  => 0.06,
                MatchEventType::FoulCommitted->value  => 0.04,
                MatchEventType::Interception->value   => 0.08,
                MatchEventType::TackleWon->value      => 0.06,
            ],
            PitchZone::MiddleThird => [
                MatchEventType::PassCompleted->value  => 0.54,
                MatchEventType::PassFailed->value     => 0.10,
                MatchEventType::DribbleSuccess->value => 0.12,
                MatchEventType::DribbleFailed->value  => 0.05,
                MatchEventType::FoulCommitted->value  => 0.03,
                MatchEventType::Interception->value   => 0.09,
                MatchEventType::TackleWon->value      => 0.06,
                MatchEventType::ShotAttempt->value    => 0.01,
            ],
            PitchZone::AttackingThird => [
                MatchEventType::PassCompleted->value  => 0.38,
                MatchEventType::PassFailed->value     => 0.09,
                MatchEventType::DribbleSuccess->value => 0.13,
                MatchEventType::DribbleFailed->value  => 0.05,
                MatchEventType::FoulCommitted->value  => 0.06,
                MatchEventType::Interception->value   => 0.08,
                MatchEventType::TackleWon->value      => 0.07,
                MatchEventType::ShotAttempt->value    => 0.14,
            ],
            PitchZone::PenaltyArea => [
                MatchEventType::PassCompleted->value  => 0.25,
                MatchEventType::PassFailed->value     => 0.08,
                MatchEventType::DribbleSuccess->value => 0.06,
                MatchEventType::DribbleFailed->value  => 0.05,
                MatchEventType::FoulCommitted->value  => 0.08,
                MatchEventType::Interception->value   => 0.07,
                MatchEventType::TackleWon->value      => 0.09,
                MatchEventType::ShotAttempt->value    => 0.32,
            ],
        };
    }

    /**
     * Corner kick — attacking team has a direct delivery into a dangerous area.
     * Shots and physical duels dominate; passing (short corner) is a secondary option.
     *
     * @return array<string, float>
     */
    private function cornerKickWeights(): array
    {
        return [
            MatchEventType::ShotAttempt->value    => 0.50,
            MatchEventType::PassCompleted->value  => 0.25,
            MatchEventType::FoulCommitted->value  => 0.15,
            MatchEventType::Interception->value   => 0.10,
        ];
    }

    /**
     * Free kick — direct shot or passing move are the primary outcomes.
     *
     * @return array<string, float>
     */
    private function freeKickWeights(): array
    {
        return [
            MatchEventType::PassCompleted->value  => 0.45,
            MatchEventType::ShotAttempt->value    => 0.35,
            MatchEventType::FoulCommitted->value  => 0.10,
            MatchEventType::DribbleSuccess->value => 0.10,
        ];
    }
}
