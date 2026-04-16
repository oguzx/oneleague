<?php

namespace Tests\Unit\Simulation;

use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\EventBaseWeightResolver;
use App\Services\Simulation\EventCandidateResolver;
use Tests\TestCase;

class EventBaseWeightResolverTest extends TestCase
{
    private EventBaseWeightResolver $resolver;
    private EventCandidateResolver  $candidateResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver          = new EventBaseWeightResolver();
        $this->candidateResolver = new EventCandidateResolver();
    }

    public function test_every_candidate_has_a_weight_entry(): void
    {
        foreach (PitchZone::cases() as $zone) {
            $candidates = $this->candidateResolver->resolve($zone, MatchPhase::Normal);
            $bag        = $this->resolver->resolve($candidates, $zone, MatchPhase::Normal);
            $weights    = $bag->toArray();

            foreach ($candidates as $event) {
                $this->assertArrayHasKey(
                    $event->value,
                    $weights,
                    "Missing base weight for {$event->value} in zone {$zone->value}",
                );
            }
        }
    }

    public function test_all_weights_are_positive(): void
    {
        foreach (PitchZone::cases() as $zone) {
            $candidates = $this->candidateResolver->resolve($zone, MatchPhase::Normal);
            $bag        = $this->resolver->resolve($candidates, $zone, MatchPhase::Normal);

            foreach ($bag->toArray() as $event => $weight) {
                $this->assertGreaterThan(0.0, $weight, "Zero/negative base weight for {$event}");
            }
        }
    }

    public function test_penalty_area_has_higher_shot_weight_than_middle_third(): void
    {
        $penaltyBag = $this->resolver->resolve(
            $this->candidateResolver->resolve(PitchZone::PenaltyArea, MatchPhase::Normal),
            PitchZone::PenaltyArea,
            MatchPhase::Normal,
        );
        $middleBag = $this->resolver->resolve(
            $this->candidateResolver->resolve(PitchZone::MiddleThird, MatchPhase::Normal),
            PitchZone::MiddleThird,
            MatchPhase::Normal,
        );

        $this->assertGreaterThan(
            $middleBag->toArray()[MatchEventType::ShotAttempt->value],
            $penaltyBag->toArray()[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_corner_kick_weights_favour_shots_over_passing(): void
    {
        $candidates = $this->candidateResolver->resolve(PitchZone::PenaltyArea, MatchPhase::CornerKick);
        $bag        = $this->resolver->resolve($candidates, PitchZone::PenaltyArea, MatchPhase::CornerKick);
        $weights    = $bag->toArray();

        $this->assertGreaterThan(
            $weights[MatchEventType::PassCompleted->value],
            $weights[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_free_kick_weights_are_present_for_all_candidates(): void
    {
        $candidates = $this->candidateResolver->resolve(PitchZone::AttackingThird, MatchPhase::FreeKick);
        $bag        = $this->resolver->resolve($candidates, PitchZone::AttackingThird, MatchPhase::FreeKick);
        $weights    = $bag->toArray();

        foreach ($candidates as $event) {
            $this->assertArrayHasKey($event->value, $weights);
        }
    }

    public function test_no_extra_events_are_introduced_beyond_candidates(): void
    {
        foreach (PitchZone::cases() as $zone) {
            $candidates    = $this->candidateResolver->resolve($zone, MatchPhase::Normal);
            $candidateKeys = array_map(fn($e) => $e->value, $candidates);
            $bag           = $this->resolver->resolve($candidates, $zone, MatchPhase::Normal);

            foreach (array_keys($bag->toArray()) as $key) {
                $this->assertContains($key, $candidateKeys, "Unexpected event '{$key}' introduced by base weight resolver");
            }
        }
    }
}
