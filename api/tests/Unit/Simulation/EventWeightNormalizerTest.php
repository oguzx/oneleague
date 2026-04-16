<?php

namespace Tests\Unit\Simulation;

use App\Data\EventWeightBag;
use App\Services\Simulation\EventWeightNormalizer;
use Tests\TestCase;

class EventWeightNormalizerTest extends TestCase
{
    private EventWeightNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new EventWeightNormalizer();
    }

    public function test_normalized_weights_sum_to_one(): void
    {
        $bag    = new EventWeightBag(['a' => 0.5, 'b' => 0.3, 'c' => 0.2]);
        $result = $this->normalizer->normalize($bag);

        $this->assertEqualsWithDelta(1.0, array_sum($result), 0.0001);
    }

    public function test_no_negative_values_in_output(): void
    {
        $bag    = new EventWeightBag(['a' => 10.0, 'b' => 5.0, 'c' => 1.0]);
        $result = $this->normalizer->normalize($bag);

        foreach ($result as $weight) {
            $this->assertGreaterThanOrEqual(0.0, $weight);
        }
    }

    public function test_all_zero_weights_produce_uniform_distribution(): void
    {
        $bag    = new EventWeightBag(['a' => 0.0, 'b' => 0.0]);
        $result = $this->normalizer->normalize($bag);

        $this->assertEqualsWithDelta(1.0, array_sum($result), 0.0001);
        $this->assertEqualsWithDelta(0.5, $result['a'], 0.0001);
        $this->assertEqualsWithDelta(0.5, $result['b'], 0.0001);
    }

    public function test_empty_bag_returns_empty_array(): void
    {
        $bag    = new EventWeightBag([]);
        $result = $this->normalizer->normalize($bag);

        $this->assertEmpty($result);
    }

    public function test_single_event_gets_probability_of_one(): void
    {
        $bag    = new EventWeightBag(['shot_attempt' => 0.75]);
        $result = $this->normalizer->normalize($bag);

        $this->assertEqualsWithDelta(1.0, $result['shot_attempt'], 0.0001);
    }

    public function test_event_keys_are_preserved_after_normalization(): void
    {
        $bag    = new EventWeightBag(['pass_completed' => 0.6, 'shot_attempt' => 0.4]);
        $result = $this->normalizer->normalize($bag);

        $this->assertArrayHasKey('pass_completed', $result);
        $this->assertArrayHasKey('shot_attempt',   $result);
    }

    public function test_proportions_are_maintained(): void
    {
        $bag    = new EventWeightBag(['a' => 3.0, 'b' => 1.0]);
        $result = $this->normalizer->normalize($bag);

        // 'a' should be exactly three times 'b'
        $this->assertEqualsWithDelta($result['b'] * 3, $result['a'], 0.0001);
    }
}
