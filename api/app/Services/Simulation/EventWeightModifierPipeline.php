<?php

namespace App\Services\Simulation;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Services\Simulation\Modifiers\EventWeightModifierInterface;

/**
 * Runs an ordered sequence of weight modifiers over an EventWeightBag.
 *
 * The pipeline is constructed with an explicit, ordered modifier list.
 * See AppServiceProvider for the registered order and the reasoning behind it.
 *
 * Modifiers are applied in insertion order. Each modifier receives the bag
 * after all previous modifiers have already run, so order is significant.
 */
class EventWeightModifierPipeline
{
    /** @param EventWeightModifierInterface[] $modifiers */
    public function __construct(private readonly array $modifiers) {}

    public function run(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void {
        foreach ($this->modifiers as $modifier) {
            $modifier->modify($bag, $state, $context);
        }
    }
}
