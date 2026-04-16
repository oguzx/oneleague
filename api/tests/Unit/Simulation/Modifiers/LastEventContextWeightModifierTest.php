<?php

namespace Tests\Unit\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\Modifiers\LastEventContextWeightModifier;
use Tests\TestCase;

class LastEventContextWeightModifierTest extends TestCase
{
    private LastEventContextWeightModifier $modifier;
    private MatchContextData               $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modifier = new LastEventContextWeightModifier();
        $this->context  = $this->makeContext();
    }

    public function test_corner_kick_phase_boosts_shot_weight(): void
    {
        $cornerBag = $this->bagWithShotAndPass();
        $normalBag = $this->bagWithShotAndPass();

        $this->modifier->modify($cornerBag, $this->makeState(MatchPhase::CornerKick), $this->context);
        $this->modifier->modify($normalBag, $this->makeState(MatchPhase::Normal),     $this->context);

        $this->assertGreaterThan(
            $normalBag->toArray()[MatchEventType::ShotAttempt->value],
            $cornerBag->toArray()[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_corner_kick_phase_reduces_pass_weight(): void
    {
        $cornerBag = $this->bagWithShotAndPass();
        $normalBag = $this->bagWithShotAndPass();

        $this->modifier->modify($cornerBag, $this->makeState(MatchPhase::CornerKick), $this->context);
        $this->modifier->modify($normalBag, $this->makeState(MatchPhase::Normal),     $this->context);

        $this->assertLessThan(
            $normalBag->toArray()[MatchEventType::PassCompleted->value],
            $cornerBag->toArray()[MatchEventType::PassCompleted->value],
        );
    }

    public function test_free_kick_phase_boosts_shot_weight(): void
    {
        $fkBag     = $this->bagWithShotAndPass();
        $normalBag = $this->bagWithShotAndPass();

        $this->modifier->modify($fkBag,     $this->makeState(MatchPhase::FreeKick), $this->context);
        $this->modifier->modify($normalBag, $this->makeState(MatchPhase::Normal),   $this->context);

        $this->assertGreaterThan(
            $normalBag->toArray()[MatchEventType::ShotAttempt->value],
            $fkBag->toArray()[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_normal_phase_leaves_weights_unchanged(): void
    {
        $bag = $this->bagWithShotAndPass();

        $this->modifier->modify($bag, $this->makeState(MatchPhase::Normal), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertEqualsWithDelta(1.0, $weight, 0.0001);
        }
    }

    public function test_after_goal_phase_leaves_weights_unchanged(): void
    {
        $bag = $this->bagWithShotAndPass();

        $this->modifier->modify($bag, $this->makeState(MatchPhase::AfterGoal), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertEqualsWithDelta(1.0, $weight, 0.0001);
        }
    }

    public function test_modifier_does_not_introduce_absent_events(): void
    {
        // Bag with only PassCompleted — ShotAttempt should not appear
        $bag = new EventWeightBag([MatchEventType::PassCompleted->value => 1.0]);

        $this->modifier->modify($bag, $this->makeState(MatchPhase::CornerKick), $this->context);

        $this->assertArrayNotHasKey(MatchEventType::ShotAttempt->value, $bag->toArray());
    }

    public function test_weights_remain_non_negative_after_context_modifier(): void
    {
        $bag = $this->bagWithShotAndPass();

        $this->modifier->modify($bag, $this->makeState(MatchPhase::CornerKick), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertGreaterThanOrEqual(0.0, $weight);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function bagWithShotAndPass(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::ShotAttempt->value   => 1.0,
            MatchEventType::PassCompleted->value => 1.0,
            MatchEventType::FoulCommitted->value => 1.0,
        ]);
    }

    private function makeState(MatchPhase $phase = MatchPhase::Normal): MatchStateData
    {
        $state                   = new MatchStateData();
        $state->homeTeamId       = 'home-id';
        $state->awayTeamId       = 'away-id';
        $state->possessionTeamId = 'home-id';
        $state->defendingTeamId  = 'away-id';
        $state->zone             = PitchZone::PenaltyArea;
        $state->phase            = $phase;
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
