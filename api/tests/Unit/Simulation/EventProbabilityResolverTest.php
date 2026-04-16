<?php

namespace Tests\Unit\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\EventBaseWeightResolver;
use App\Services\Simulation\EventCandidateResolver;
use App\Services\Simulation\EventProbabilityResolver;
use App\Services\Simulation\EventWeightModifierPipeline;
use App\Services\Simulation\EventWeightNormalizer;
use App\Services\Simulation\Modifiers\FatigueEventWeightModifier;
use App\Services\Simulation\Modifiers\LastEventContextWeightModifier;
use App\Services\Simulation\Modifiers\MomentumEventWeightModifier;
use App\Services\Simulation\Modifiers\StatEventWeightModifier;
use Tests\TestCase;

class EventProbabilityResolverTest extends TestCase
{
    private EventProbabilityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->makeResolver();
    }

    public function test_probabilities_sum_to_one(): void
    {
        $weights = $this->resolver->resolve($this->makeState(), $this->makeContext());

        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.0001);
    }

    public function test_all_weights_are_non_negative(): void
    {
        $weights = $this->resolver->resolve($this->makeState(), $this->makeContext());

        foreach ($weights as $event => $weight) {
            $this->assertGreaterThanOrEqual(0.0, $weight, "Negative weight for {$event}");
        }
    }

    public function test_shot_attempt_is_more_likely_in_penalty_area_than_middle_third(): void
    {
        $middleState  = $this->makeState(PitchZone::MiddleThird);
        $penaltyState = $this->makeState(PitchZone::PenaltyArea);
        $context      = $this->makeContext();

        $middleWeights  = $this->resolver->resolve($middleState, $context);
        $penaltyWeights = $this->resolver->resolve($penaltyState, $context);

        $this->assertGreaterThan(
            $middleWeights[MatchEventType::ShotAttempt->value] ?? 0,
            $penaltyWeights[MatchEventType::ShotAttempt->value] ?? 0,
        );
    }

    public function test_high_fatigue_increases_foul_probability(): void
    {
        $lowFatigueState  = $this->makeState(PitchZone::MiddleThird, homeFatigue: 0.0);
        $highFatigueState = $this->makeState(PitchZone::MiddleThird, homeFatigue: 0.9);
        $context          = $this->makeContext();

        $low  = $this->resolver->resolve($lowFatigueState, $context);
        $high = $this->resolver->resolve($highFatigueState, $context);

        $this->assertGreaterThan(
            $low[MatchEventType::FoulCommitted->value],
            $high[MatchEventType::FoulCommitted->value],
        );
    }

    public function test_returns_only_valid_event_types(): void
    {
        $weights     = $this->resolver->resolve($this->makeState(), $this->makeContext());
        $validValues = array_column(MatchEventType::cases(), 'value');

        foreach (array_keys($weights) as $key) {
            $this->assertContains($key, $validValues);
        }
    }

    public function test_corner_kick_phase_has_higher_shot_probability_than_normal(): void
    {
        $normalState = $this->makeState(PitchZone::PenaltyArea, phase: MatchPhase::Normal);
        $cornerState = $this->makeState(PitchZone::PenaltyArea, phase: MatchPhase::CornerKick);
        $context     = $this->makeContext();

        $normal = $this->resolver->resolve($normalState, $context);
        $corner = $this->resolver->resolve($cornerState, $context);

        $this->assertGreaterThan(
            $normal[MatchEventType::ShotAttempt->value] ?? 0,
            $corner[MatchEventType::ShotAttempt->value] ?? 0,
        );
    }

    public function test_defensive_third_has_no_shot_attempt(): void
    {
        $state   = $this->makeState(PitchZone::DefensiveThird);
        $weights = $this->resolver->resolve($state, $this->makeContext());

        $this->assertArrayNotHasKey(MatchEventType::ShotAttempt->value, $weights);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeResolver(): EventProbabilityResolver
    {
        return new EventProbabilityResolver(
            new EventCandidateResolver(),
            new EventBaseWeightResolver(),
            new EventWeightModifierPipeline([
                new LastEventContextWeightModifier(),
                new StatEventWeightModifier(),
                new FatigueEventWeightModifier(),
                new MomentumEventWeightModifier(),
            ]),
            new EventWeightNormalizer(),
        );
    }

    private function makeState(
        PitchZone  $zone       = PitchZone::MiddleThird,
        float      $homeFatigue = 0.0,
        MatchPhase $phase      = MatchPhase::Normal,
    ): MatchStateData {
        $state                   = new MatchStateData();
        $state->homeTeamId       = 'home-id';
        $state->awayTeamId       = 'away-id';
        $state->possessionTeamId = 'home-id';
        $state->defendingTeamId  = 'away-id';
        $state->zone             = $zone;
        $state->phase            = $phase;
        $state->homeFatigue      = $homeFatigue;
        return $state;
    }

    private function makeContext(float $attack = 0.85, float $defense = 0.80): MatchContextData
    {
        $profile = fn(string $id) => new TeamStrengthProfileData(
            teamId:              $id,
            attack:              $attack,
            defense:             $defense,
            midfield:            0.83,
            finishing:           0.85,
            goalkeeper:          0.82,
            pressing:            0.80,
            setPiece:            0.78,
            consistency:         0.85,
            fatigueResistance:   0.85,
            bigMatchPerformance: 0.87,
            winnerMentality:     0.9,
            loserMentality:      0.8,
            homeAdvantageRaw:    8,
        );

        return new MatchContextData(
            fixtureId:                     'fixture-id',
            homeTeamId:                    'home-id',
            awayTeamId:                    'away-id',
            homeProfile:                   $profile('home-id'),
            awayProfile:                   $profile('away-id'),
            homeAdvantageFactor:           0.8,
            tempoFactor:                   0.85,
            refStrictnessFactor:           0.6,
            expectedHomeAttackingPressure: 0.7,
            expectedAwayAttackingPressure: 0.65,
        );
    }
}
