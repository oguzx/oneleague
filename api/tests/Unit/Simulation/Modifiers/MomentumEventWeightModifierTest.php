<?php

namespace Tests\Unit\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\MatchConstants;
use App\Services\Simulation\Modifiers\MomentumEventWeightModifier;
use Tests\TestCase;

class MomentumEventWeightModifierTest extends TestCase
{
    private MomentumEventWeightModifier $modifier;
    private MatchContextData            $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modifier = new MomentumEventWeightModifier();
        $this->context  = $this->makeContext();
    }

    public function test_high_momentum_increases_shot_weight(): void
    {
        $highBag    = $this->bagWithPositivePlay();
        $neutralBag = $this->bagWithPositivePlay();

        $this->modifier->modify($highBag,    $this->makeState(homeMomentum: 1.0), $this->context);
        $this->modifier->modify($neutralBag, $this->makeState(homeMomentum: MatchConstants::MOMENTUM_NEUTRAL), $this->context);

        $this->assertGreaterThan(
            $neutralBag->toArray()[MatchEventType::ShotAttempt->value],
            $highBag->toArray()[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_low_momentum_reduces_shot_weight(): void
    {
        $lowBag     = $this->bagWithPositivePlay();
        $neutralBag = $this->bagWithPositivePlay();

        $this->modifier->modify($lowBag,     $this->makeState(homeMomentum: 0.0), $this->context);
        $this->modifier->modify($neutralBag, $this->makeState(homeMomentum: MatchConstants::MOMENTUM_NEUTRAL), $this->context);

        $this->assertLessThan(
            $neutralBag->toArray()[MatchEventType::ShotAttempt->value],
            $lowBag->toArray()[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_neutral_momentum_does_not_change_weights(): void
    {
        $bag = $this->bagWithPositivePlay();

        $this->modifier->modify($bag, $this->makeState(homeMomentum: MatchConstants::MOMENTUM_NEUTRAL), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertEqualsWithDelta(1.0, $weight, 0.0001);
        }
    }

    public function test_modifier_does_not_affect_events_not_in_bag(): void
    {
        $bag = new EventWeightBag([MatchEventType::FoulCommitted->value => 1.0]);

        $this->modifier->modify($bag, $this->makeState(homeMomentum: 1.0), $this->context);

        // FoulCommitted is not a momentum target — should be unchanged
        $this->assertEqualsWithDelta(1.0, $bag->toArray()[MatchEventType::FoulCommitted->value], 0.0001);
    }

    public function test_weights_remain_non_negative_at_zero_momentum(): void
    {
        $bag = $this->bagWithPositivePlay();

        $this->modifier->modify($bag, $this->makeState(homeMomentum: 0.0), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertGreaterThanOrEqual(0.0, $weight);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function bagWithPositivePlay(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::ShotAttempt->value    => 1.0,
            MatchEventType::PassCompleted->value  => 1.0,
            MatchEventType::DribbleSuccess->value => 1.0,
        ]);
    }

    private function makeState(float $homeMomentum = MatchConstants::MOMENTUM_NEUTRAL): MatchStateData
    {
        $state                   = new MatchStateData();
        $state->homeTeamId       = 'home-id';
        $state->awayTeamId       = 'away-id';
        $state->possessionTeamId = 'home-id';
        $state->defendingTeamId  = 'away-id';
        $state->zone             = PitchZone::MiddleThird;
        $state->phase            = MatchPhase::Normal;
        $state->homeMomentum     = $homeMomentum;
        return $state;
    }

    private function makeContext(): MatchContextData
    {
        $profile = fn(string $id) => new TeamStrengthProfileData(
            teamId: $id, attack: 0.80, defense: 0.80, midfield: 0.80,
            finishing: 0.80, goalkeeper: 0.80, pressing: 0.80, setPiece: 0.78,
            consistency: 0.80, fatigueResistance: 0.80, bigMatchPerformance: 0.80,
            winnerMentality: 0.8, loserMentality: 0.8, homeAdvantageRaw: 7,
        );

        return new MatchContextData(
            fixtureId: 'f', homeTeamId: 'home-id', awayTeamId: 'away-id',
            homeProfile: $profile('home-id'), awayProfile: $profile('away-id'),
            homeAdvantageFactor: 0.7, tempoFactor: 0.8, refStrictnessFactor: 0.6,
            expectedHomeAttackingPressure: 0.7, expectedAwayAttackingPressure: 0.6,
        );
    }
}
