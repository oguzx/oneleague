<?php

namespace Tests\Unit\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\Modifiers\StatEventWeightModifier;
use Tests\TestCase;

class StatEventWeightModifierTest extends TestCase
{
    private StatEventWeightModifier $modifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modifier = new StatEventWeightModifier();
    }

    public function test_stronger_attacker_increases_shot_weight(): void
    {
        $strongBag = $this->bagWithShot();
        $weakBag   = $this->bagWithShot();

        $this->modifier->modify($strongBag, $this->makeState(), $this->makeContext(attack: 0.95, defending_defense: 0.40));
        $this->modifier->modify($weakBag,   $this->makeState(), $this->makeContext(attack: 0.40, defending_defense: 0.95));

        $this->assertGreaterThan(
            $weakBag->toArray()[MatchEventType::ShotAttempt->value],
            $strongBag->toArray()[MatchEventType::ShotAttempt->value],
        );
    }

    public function test_better_midfield_reduces_pass_failed_weight(): void
    {
        $goodMidfieldBag = $this->bagWithPassFailed();
        $poorMidfieldBag = $this->bagWithPassFailed();

        $this->modifier->modify($goodMidfieldBag, $this->makeState(), $this->makeContext(attack: 0.80, midfield: 0.95));
        $this->modifier->modify($poorMidfieldBag, $this->makeState(), $this->makeContext(attack: 0.80, midfield: 0.20));

        $this->assertLessThan(
            $poorMidfieldBag->toArray()[MatchEventType::PassFailed->value],
            $goodMidfieldBag->toArray()[MatchEventType::PassFailed->value],
        );
    }

    public function test_stronger_presser_increases_interception_weight(): void
    {
        $highPressBag = $this->bagWithInterception();
        $lowPressBag  = $this->bagWithInterception();

        $this->modifier->modify($highPressBag, $this->makeState(), $this->makeContext(attack: 0.80, defending_pressing: 0.95));
        $this->modifier->modify($lowPressBag,  $this->makeState(), $this->makeContext(attack: 0.80, defending_pressing: 0.20));

        $this->assertGreaterThan(
            $lowPressBag->toArray()[MatchEventType::Interception->value],
            $highPressBag->toArray()[MatchEventType::Interception->value],
        );
    }

    public function test_modifier_is_silent_for_absent_events(): void
    {
        // Bag without ShotAttempt — modifier should not introduce it
        $bag = new EventWeightBag([MatchEventType::PassCompleted->value => 1.0]);

        $this->modifier->modify($bag, $this->makeState(), $this->makeContext());

        $this->assertArrayNotHasKey(MatchEventType::ShotAttempt->value, $bag->toArray());
    }

    public function test_weights_remain_non_negative_after_modification(): void
    {
        $bag = $this->bagWithAllEvents();

        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(attack: 0.0, defending_defense: 1.0));

        foreach ($bag->toArray() as $event => $weight) {
            $this->assertGreaterThanOrEqual(0.0, $weight, "Negative weight for {$event} after stat modifier");
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function bagWithShot(): EventWeightBag
    {
        return new EventWeightBag([MatchEventType::ShotAttempt->value => 1.0]);
    }

    private function bagWithPassFailed(): EventWeightBag
    {
        return new EventWeightBag([MatchEventType::PassFailed->value => 1.0]);
    }

    private function bagWithInterception(): EventWeightBag
    {
        return new EventWeightBag([MatchEventType::Interception->value => 1.0]);
    }

    private function bagWithAllEvents(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::PassCompleted->value  => 1.0,
            MatchEventType::PassFailed->value     => 1.0,
            MatchEventType::DribbleSuccess->value => 1.0,
            MatchEventType::DribbleFailed->value  => 1.0,
            MatchEventType::FoulCommitted->value  => 1.0,
            MatchEventType::Interception->value   => 1.0,
            MatchEventType::TackleWon->value      => 1.0,
            MatchEventType::ShotAttempt->value    => 1.0,
        ]);
    }

    private function makeState(): MatchStateData
    {
        $state                   = new MatchStateData();
        $state->homeTeamId       = 'home-id';
        $state->awayTeamId       = 'away-id';
        $state->possessionTeamId = 'home-id';
        $state->defendingTeamId  = 'away-id';
        $state->zone             = PitchZone::MiddleThird;
        $state->phase            = MatchPhase::Normal;
        return $state;
    }

    private function makeContext(
        float $attack             = 0.80,
        float $midfield           = 0.80,
        float $defending_defense  = 0.80,
        float $defending_pressing = 0.80,
    ): MatchContextData {
        $homeProfile = new TeamStrengthProfileData(
            teamId: 'home-id', attack: $attack, defense: 0.80, midfield: $midfield,
            finishing: 0.80, goalkeeper: 0.80, pressing: 0.80, setPiece: 0.78,
            consistency: 0.80, fatigueResistance: 0.80, bigMatchPerformance: 0.80,
            winnerMentality: 0.8, loserMentality: 0.8, homeAdvantageRaw: 7,
        );
        $awayProfile = new TeamStrengthProfileData(
            teamId: 'away-id', attack: 0.80, defense: $defending_defense, midfield: 0.80,
            finishing: 0.80, goalkeeper: 0.80, pressing: $defending_pressing, setPiece: 0.78,
            consistency: 0.80, fatigueResistance: 0.80, bigMatchPerformance: 0.80,
            winnerMentality: 0.8, loserMentality: 0.8, homeAdvantageRaw: 7,
        );

        return new MatchContextData(
            fixtureId: 'f', homeTeamId: 'home-id', awayTeamId: 'away-id',
            homeProfile: $homeProfile, awayProfile: $awayProfile,
            homeAdvantageFactor: 0.7, tempoFactor: 0.8, refStrictnessFactor: 0.6,
            expectedHomeAttackingPressure: 0.7, expectedAwayAttackingPressure: 0.6,
        );
    }
}
