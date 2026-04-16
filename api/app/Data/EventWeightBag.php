<?php

namespace App\Data;

use App\Enums\MatchEventType;

/**
 * Mutable bag of raw event weights used throughout the probability pipeline.
 *
 * Weights are raw scores and do not need to sum to 1.0.
 * Normalization is a separate final step (EventWeightNormalizer).
 *
 * Modifiers call scale() or shift() — never set weights directly —
 * so relative balance is always preserved.
 */
class EventWeightBag
{
    /** @param array<string, float> $weights MatchEventType::value => raw weight */
    public function __construct(private array $weights) {}

    /**
     * Multiply one event's weight by a factor.
     * Silent no-op if the event is not in the bag.
     * Result is clamped to zero minimum.
     */
    public function scale(MatchEventType $type, float $factor): void
    {
        if (isset($this->weights[$type->value])) {
            $this->weights[$type->value] = max(0.0, $this->weights[$type->value] * $factor);
        }
    }

    /**
     * Add a flat delta to one event's weight.
     * Silent no-op if the event is not in the bag.
     * Result is clamped to zero minimum.
     */
    public function shift(MatchEventType $type, float $delta): void
    {
        if (isset($this->weights[$type->value])) {
            $this->weights[$type->value] = max(0.0, $this->weights[$type->value] + $delta);
        }
    }

    public function has(MatchEventType $type): bool
    {
        return isset($this->weights[$type->value]);
    }

    /** @return array<string, float> */
    public function toArray(): array
    {
        return $this->weights;
    }
}
