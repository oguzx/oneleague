<?php

namespace App\Services\Simulation;

use App\Data\EventWeightBag;

/**
 * Converts an EventWeightBag of raw scores into a valid probability distribution
 * (all values non-negative, sum = 1.0).
 *
 * Edge-case handling:
 *   - Empty bag      → returns empty array; caller is responsible for handling this.
 *   - All-zero bag   → returns uniform distribution across all present events.
 *   - Negative weights should not reach here; EventWeightBag clamps to zero on write.
 *
 * @return array<string, float>
 */
class EventWeightNormalizer
{
    public function normalize(EventWeightBag $bag): array
    {
        $weights = $bag->toArray();

        if (empty($weights)) {
            return [];
        }

        $total = array_sum($weights);

        if ($total <= 0.0) {
            return $this->uniformDistribution($weights);
        }

        return array_map(fn(float $w) => $w / $total, $weights);
    }

    /** @param array<string, float> $weights */
    private function uniformDistribution(array $weights): array
    {
        $uniform = 1.0 / count($weights);
        return array_map(fn() => $uniform, $weights);
    }
}
