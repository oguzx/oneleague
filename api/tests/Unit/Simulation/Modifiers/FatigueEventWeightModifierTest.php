<?php

namespace Tests\Unit\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\Modifiers\FatigueEventWeightModifier;
use Tests\TestCase;

class FatigueEventWeightModifierTest extends TestCase
{
    private FatigueEventWeightModifier $modifier;
    private MatchContextData           $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modifier = new FatigueEventWeightModifier();
        $this->context  = $this->makeContext();
    }

    public function test_high_fatigue_increases_pass_failed_weight(): void
    {
        $lowBag  = $this->bagWithErrors();
        $highBag = $this->bagWithErrors();

        $this->modifier->modify($lowBag,  $this->makeState(homeFatigue: 0.0), $this->context);
        $this->modifier->modify($highBag, $this->makeState(homeFatigue: 0.9), $this->context);

        $this->assertGreaterThan(
            $lowBag->toArray()[MatchEventType::PassFailed->value],
            $highBag->toArray()[MatchEventType::PassFailed->value],
        );
    }

    public function test_high_fatigue_increases_foul_committed_weight(): void
    {
        $lowBag  = $this->bagWithErrors();
        $highBag = $this->bagWithErrors();

        $this->modifier->modify($lowBag,  $this->makeState(homeFatigue: 0.0), $this->context);
        $this->modifier->modify($highBag, $this->makeState(homeFatigue: 0.9), $this->context);

        $this->assertGreaterThan(
            $lowBag->toArray()[MatchEventType::FoulCommitted->value],
            $highBag->toArray()[MatchEventType::FoulCommitted->value],
        );
    }

    public function test_zero_fatigue_leaves_weights_unchanged(): void
    {
        $bag = new EventWeightBag([
            MatchEventType::PassFailed->value    => 1.0,
            MatchEventType::DribbleFailed->value => 1.0,
            MatchEventType::FoulCommitted->value => 1.0,
        ]);

        $this->modifier->modify($bag, $this->makeState(homeFatigue: 0.0), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertEqualsWithDelta(1.0, $weight, 0.0001);
        }
    }

    public function test_modifier_does_not_affect_events_not_in_bag(): void
    {
        $bag = new EventWeightBag([MatchEventType::PassCompleted->value => 1.0]);

        $before = $bag->toArray()[MatchEventType::PassCompleted->value];
        $this->modifier->modify($bag, $this->makeState(homeFatigue: 1.0), $this->context);
        $after  = $bag->toArray()[MatchEventType::PassCompleted->value];

        // PassCompleted is not a fatigue target — should be unchanged
        $this->assertEqualsWithDelta($before, $after, 0.0001);
    }

    public function test_weights_remain_non_negative_at_maximum_fatigue(): void
    {
        $bag = $this->bagWithErrors();

        $this->modifier->modify($bag, $this->makeState(homeFatigue: 1.0), $this->context);

        foreach ($bag->toArray() as $weight) {
            $this->assertGreaterThanOrEqual(0.0, $weight);
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function bagWithErrors(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::PassFailed->value    => 1.0,
            MatchEventType::DribbleFailed->value => 1.0,
            MatchEventType::FoulCommitted->value => 1.0,
        ]);
    }

    private function makeState(float $homeFatigue = 0.0): MatchStateData
    {
        $state                   = new MatchStateData();
        $state->homeTeamId       = 'home-id';
        $state->awayTeamId       = 'away-id';
        $state->possessionTeamId = 'home-id';
        $state->defendingTeamId  = 'away-id';
        $state->zone             = PitchZone::MiddleThird;
        $state->phase            = MatchPhase::Normal;
        $state->homeFatigue      = $homeFatigue;
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
