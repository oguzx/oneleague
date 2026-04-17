<?php

namespace Tests\Unit\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Enums\WeatherCondition;
use App\Services\Simulation\Modifiers\WeatherEventWeightModifier;
use Tests\TestCase;

class WeatherEventWeightModifierTest extends TestCase
{
    private WeatherEventWeightModifier $modifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modifier = new WeatherEventWeightModifier();
    }

    public function test_rain_increases_pass_failed_weight(): void
    {
        $bag = $this->bagWithPassEvents();
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Rain));

        $this->assertGreaterThan(1.0, $bag->toArray()[MatchEventType::PassFailed->value]);
    }

    public function test_rain_decreases_pass_completed_weight(): void
    {
        $bag = $this->bagWithPassEvents();
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Rain));

        $this->assertLessThan(1.0, $bag->toArray()[MatchEventType::PassCompleted->value]);
    }

    public function test_snow_decreases_dribble_success_weight(): void
    {
        $bag = $this->bagWithDribbleAndShot();
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Snow));

        $this->assertLessThan(1.0, $bag->toArray()[MatchEventType::DribbleSuccess->value]);
    }

    public function test_snow_decreases_shot_attempt_weight(): void
    {
        $bag = $this->bagWithDribbleAndShot();
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Snow));

        $this->assertLessThan(1.0, $bag->toArray()[MatchEventType::ShotAttempt->value]);
    }

    public function test_heat_increases_foul_committed_weight(): void
    {
        $bag = new EventWeightBag([MatchEventType::FoulCommitted->value => 1.0]);
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Heat));

        $this->assertGreaterThan(1.0, $bag->toArray()[MatchEventType::FoulCommitted->value]);
    }

    public function test_clear_leaves_all_weights_unchanged(): void
    {
        $bag = $this->bagWithAllWeatherTargets();
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Clear));

        foreach ($bag->toArray() as $weight) {
            $this->assertEqualsWithDelta(1.0, $weight, 0.0001);
        }
    }

    public function test_modifier_does_not_introduce_absent_events(): void
    {
        $bag = new EventWeightBag([MatchEventType::PassCompleted->value => 1.0]);
        $this->modifier->modify($bag, $this->makeState(), $this->makeContext(WeatherCondition::Snow));

        $this->assertArrayNotHasKey(MatchEventType::ShotAttempt->value, $bag->toArray());
        $this->assertArrayNotHasKey(MatchEventType::DribbleSuccess->value, $bag->toArray());
    }

    public function test_all_conditions_produce_non_negative_weights(): void
    {
        foreach (WeatherCondition::cases() as $condition) {
            $bag = $this->bagWithAllWeatherTargets();
            $this->modifier->modify($bag, $this->makeState(), $this->makeContext($condition));

            foreach ($bag->toArray() as $event => $weight) {
                $this->assertGreaterThanOrEqual(0.0, $weight, "Negative weight for {$event} under {$condition->value}");
            }
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function bagWithPassEvents(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::PassCompleted->value => 1.0,
            MatchEventType::PassFailed->value    => 1.0,
            MatchEventType::DribbleFailed->value => 1.0,
        ]);
    }

    private function bagWithDribbleAndShot(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::DribbleSuccess->value => 1.0,
            MatchEventType::DribbleFailed->value  => 1.0,
            MatchEventType::ShotAttempt->value    => 1.0,
        ]);
    }

    private function bagWithAllWeatherTargets(): EventWeightBag
    {
        return new EventWeightBag([
            MatchEventType::PassCompleted->value  => 1.0,
            MatchEventType::PassFailed->value     => 1.0,
            MatchEventType::DribbleSuccess->value => 1.0,
            MatchEventType::DribbleFailed->value  => 1.0,
            MatchEventType::ShotAttempt->value    => 1.0,
            MatchEventType::FoulCommitted->value  => 1.0,
            MatchEventType::CornerWon->value      => 1.0,
        ]);
    }

    private function makeState(): MatchStateData
    {
        $state = new MatchStateData(
            homeTeamId: 'home-id',
            awayTeamId: 'away-id',
        );
        $state->setZone(PitchZone::MiddleThird);
        $state->setPhase(MatchPhase::Normal);
        return $state;
    }

    private function makeContext(WeatherCondition $weather = WeatherCondition::Clear): MatchContextData
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
            weather: $weather, fatigueFactor: $weather->fatigueFactor(),
        );
    }
}
