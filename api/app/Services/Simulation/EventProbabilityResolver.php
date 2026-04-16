<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchStateData;

/**
 * Orchestrates the event probability resolution pipeline for a single tick.
 *
 * Pipeline steps (in order):
 *   1. EventCandidateResolver    — which event types are valid right now
 *   2. EventBaseWeightResolver   — raw zone/phase weights for those candidates
 *   3. EventWeightModifierPipeline — contextual, stat, fatigue, momentum modifiers
 *   4. EventWeightNormalizer     — convert raw scores → probability distribution
 *
 * Returns a map of MatchEventType::value → probability (sums to 1.0).
 *
 * @return array<string, float>
 */
class EventProbabilityResolver
{
    public function __construct(
        private readonly EventCandidateResolver      $candidateResolver,
        private readonly EventBaseWeightResolver     $baseWeightResolver,
        private readonly EventWeightModifierPipeline $modifierPipeline,
        private readonly EventWeightNormalizer       $normalizer,
    ) {}

    /** @return array<string, float> */
    public function resolve(MatchStateData $state, MatchContextData $context): array
    {
        $candidates = $this->candidateResolver->resolve($state->zone, $state->phase);
        $bag        = $this->baseWeightResolver->resolve($candidates, $state->zone, $state->phase);

        $this->modifierPipeline->run($bag, $state, $context);

        return $this->normalizer->normalize($bag);
    }
}
