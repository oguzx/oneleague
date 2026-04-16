<?php

namespace App\Services\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;

/**
 * Contract for all event weight modifiers in the probability pipeline.
 *
 * Each modifier receives the weight bag and mutates it in-place.
 * Modifiers must only scale or shift existing entries — never introduce
 * new events that were not placed by the candidate and base-weight steps.
 *
 * An interface is used here because the pipeline iterates over an ordered
 * collection of modifiers polymorphically. This is the one place in the
 * simulation pipeline where polymorphism adds real value.
 */
interface EventWeightModifierInterface
{
    public function modify(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void;
}
