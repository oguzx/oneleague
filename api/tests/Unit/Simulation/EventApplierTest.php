<?php

namespace Tests\Unit\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\PitchZone;
use App\Enums\WeatherCondition;
use App\Services\Simulation\EventApplier;
use Tests\TestCase;

class EventApplierTest extends TestCase
{
    private EventApplier     $applier;
    private MatchContextData $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applier = new EventApplier();
        $this->context = $this->makeContext();
    }

    public function test_goal_increments_home_score_when_home_has_possession(): void
    {
        $state = $this->makeState();
        $state->setPossessionTeam($state->homeTeamId());
        $state->setDefendingTeam($state->awayTeamId());

        $this->applier->apply(MatchEventType::Goal, $state, $this->context);

        $this->assertEquals(1, $state->homeScore());
        $this->assertEquals(0, $state->awayScore());
    }

    public function test_goal_increments_away_score_when_away_has_possession(): void
    {
        $state = $this->makeState();
        $state->setPossessionTeam($state->awayTeamId());
        $state->setDefendingTeam($state->homeTeamId());

        $this->applier->apply(MatchEventType::Goal, $state, $this->context);

        $this->assertEquals(0, $state->homeScore());
        $this->assertEquals(1, $state->awayScore());
    }

    public function test_score_never_goes_negative(): void
    {
        $state = $this->makeState();
        // No negative score possible since we only increment
        $this->assertEquals(0, $state->homeScore());
        $this->assertEquals(0, $state->awayScore());
    }

    public function test_pass_failed_switches_possession(): void
    {
        $state = $this->makeState();
        $originalPossession = $state->possessionTeamId();

        $this->applier->apply(MatchEventType::PassFailed, $state, $this->context);

        $this->assertNotEquals($originalPossession, $state->possessionTeamId());
    }

    public function test_dribble_failed_switches_possession(): void
    {
        $state = $this->makeState();
        $originalPossession = $state->possessionTeamId();

        $this->applier->apply(MatchEventType::DribbleFailed, $state, $this->context);

        $this->assertNotEquals($originalPossession, $state->possessionTeamId());
    }

    public function test_interception_switches_possession(): void
    {
        $state = $this->makeState();
        $originalPossession = $state->possessionTeamId();

        $this->applier->apply(MatchEventType::Interception, $state, $this->context);

        $this->assertNotEquals($originalPossession, $state->possessionTeamId());
    }

    public function test_pass_completed_does_not_switch_possession(): void
    {
        $state = $this->makeState();
        $original = $state->possessionTeamId();

        $this->applier->apply(MatchEventType::PassCompleted, $state, $this->context);

        $this->assertEquals($original, $state->possessionTeamId());
    }

    public function test_dribble_success_advances_zone(): void
    {
        $state = $this->makeState();
        $state->setZone(PitchZone::MiddleThird);

        $this->applier->apply(MatchEventType::DribbleSuccess, $state, $this->context);

        $this->assertEquals(PitchZone::AttackingThird, $state->zone());
    }

    public function test_kickoff_resets_zone_to_middle_third(): void
    {
        $state = $this->makeState();
        $state->setZone(PitchZone::PenaltyArea);

        $this->applier->apply(MatchEventType::Kickoff, $state, $this->context);

        $this->assertEquals(PitchZone::MiddleThird, $state->zone());
    }

    public function test_half_time_switches_possession_to_away(): void
    {
        $state = $this->makeState();
        $state->setPossessionTeam($state->homeTeamId());
        $state->setDefendingTeam($state->awayTeamId());

        $this->applier->apply(MatchEventType::HalfTime, $state, $this->context);

        $this->assertEquals($state->awayTeamId(), $state->possessionTeamId());
        $this->assertEquals(2, $state->currentHalf());
    }

    public function test_full_time_marks_match_as_finished(): void
    {
        $state = $this->makeState();

        $this->applier->apply(MatchEventType::FullTime, $state, $this->context);

        $this->assertTrue($state->isFinished());
    }

    public function test_shot_attempt_produces_shot_and_outcome_events(): void
    {
        mt_srand(42); // deterministic
        $state = $this->makeState();
        $state->setZone(PitchZone::PenaltyArea);

        $events = $this->applier->apply(MatchEventType::ShotAttempt, $state, $this->context);

        // shot_attempt + outcome = at least 2 events
        $this->assertGreaterThanOrEqual(2, count($events));
        $this->assertEquals(MatchEventType::ShotAttempt, $events[0]->type);

        $outcomeTypes = [
            MatchEventType::Goal,
            MatchEventType::ShotSaved,
            MatchEventType::ShotBlocked,
            MatchEventType::ShotOffTarget,
        ];
        $this->assertContains($events[1]->type, $outcomeTypes);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeState(): MatchStateData
    {
        $state = new MatchStateData(
            homeTeamId: 'home-id',
            awayTeamId: 'away-id',
        );
        $state->setZone(PitchZone::MiddleThird);
        return $state;
    }

    private function makeContext(): MatchContextData
    {
        $profile = fn(string $id) => new TeamStrengthProfileData(
            teamId: $id, attack: 0.85, defense: 0.80, midfield: 0.83,
            finishing: 0.85, goalkeeper: 0.82, pressing: 0.80, setPiece: 0.78,
            consistency: 0.85, fatigueResistance: 0.85, bigMatchPerformance: 0.87,
            winnerMentality: 0.9, loserMentality: 0.8, homeAdvantageRaw: 8,
        );

        return new MatchContextData(
            fixtureId: 'fixture-id', homeTeamId: 'home-id', awayTeamId: 'away-id',
            homeProfile: $profile('home-id'), awayProfile: $profile('away-id'),
            homeAdvantageFactor: 0.8, tempoFactor: 0.85, refStrictnessFactor: 0.6,
            expectedHomeAttackingPressure: 0.7, expectedAwayAttackingPressure: 0.65,
            weather: WeatherCondition::Clear, fatigueFactor: 1.0,
        );
    }
}
