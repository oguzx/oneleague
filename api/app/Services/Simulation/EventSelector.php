<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TickDecisionData;
use App\Enums\MatchEventType;

/**
 * Picks one event from the normalized probability weights using weighted random selection.
 * Deterministic when the RNG has been seeded before simulation.
 */
class EventSelector
{
    public function __construct(
        private readonly EventProbabilityResolver $resolver,
    ) {}

    public function select(MatchStateData $state, MatchContextData $context): TickDecisionData
    {
        $weights = $this->resolver->resolve($state, $context);
        $event   = $this->weightedRandom($weights);

        return new TickDecisionData(
            event:     $event,
            isVisible: $event->isVisible(),
        );
    }

    /** Weighted random selection over a normalized probability array. */
    private function weightedRandom(array $weights): MatchEventType
    {
        $roll       = mt_rand(0, 1_000_000) / 1_000_000.0;
        $cumulative = 0.0;

        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return MatchEventType::from($value);
            }
        }

        // Floating-point safety: return last key
        return MatchEventType::from(array_key_last($weights));
    }
}
