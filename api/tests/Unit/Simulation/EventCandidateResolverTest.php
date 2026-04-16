<?php

namespace Tests\Unit\Simulation;

use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\EventCandidateResolver;
use Tests\TestCase;

class EventCandidateResolverTest extends TestCase
{
    private EventCandidateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new EventCandidateResolver();
    }

    // ─── Defensive third ──────────────────────────────────────────────────────

    public function test_defensive_third_does_not_include_shot_attempt(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::DefensiveThird, MatchPhase::Normal);

        $this->assertNotContains(MatchEventType::ShotAttempt, $candidates);
    }

    public function test_defensive_third_includes_standard_passing_and_duel_events(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::DefensiveThird, MatchPhase::Normal);

        $this->assertContains(MatchEventType::PassCompleted, $candidates);
        $this->assertContains(MatchEventType::Interception,  $candidates);
        $this->assertContains(MatchEventType::TackleWon,     $candidates);
    }

    // ─── Attacking zones ──────────────────────────────────────────────────────

    public function test_middle_third_includes_shot_attempt(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::MiddleThird, MatchPhase::Normal);

        $this->assertContains(MatchEventType::ShotAttempt, $candidates);
    }

    public function test_attacking_third_includes_shot_attempt(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::AttackingThird, MatchPhase::Normal);

        $this->assertContains(MatchEventType::ShotAttempt, $candidates);
    }

    public function test_penalty_area_includes_shot_attempt(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::PenaltyArea, MatchPhase::Normal);

        $this->assertContains(MatchEventType::ShotAttempt, $candidates);
    }

    // ─── Corner kick phase ────────────────────────────────────────────────────

    public function test_corner_kick_phase_includes_shot_and_pass(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::PenaltyArea, MatchPhase::CornerKick);

        $this->assertContains(MatchEventType::ShotAttempt,   $candidates);
        $this->assertContains(MatchEventType::PassCompleted, $candidates);
    }

    public function test_corner_kick_phase_excludes_open_play_only_events(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::PenaltyArea, MatchPhase::CornerKick);

        $this->assertNotContains(MatchEventType::DribbleFailed, $candidates);
        $this->assertNotContains(MatchEventType::TackleWon,     $candidates);
        $this->assertNotContains(MatchEventType::PassFailed,    $candidates);
    }

    // ─── Free kick phase ──────────────────────────────────────────────────────

    public function test_free_kick_phase_includes_shot_and_pass(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::MiddleThird, MatchPhase::FreeKick);

        $this->assertContains(MatchEventType::ShotAttempt,   $candidates);
        $this->assertContains(MatchEventType::PassCompleted, $candidates);
    }

    public function test_free_kick_phase_excludes_tackle_and_interception(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::MiddleThird, MatchPhase::FreeKick);

        $this->assertNotContains(MatchEventType::TackleWon,   $candidates);
        $this->assertNotContains(MatchEventType::Interception, $candidates);
    }

    // ─── AfterGoal phase uses zone-based open play ────────────────────────────

    public function test_after_goal_phase_falls_back_to_zone_candidates(): void
    {
        $candidates = $this->resolver->resolve(PitchZone::MiddleThird, MatchPhase::AfterGoal);

        $this->assertContains(MatchEventType::PassCompleted, $candidates);
        $this->assertContains(MatchEventType::ShotAttempt,   $candidates);
    }

    // ─── No surprise events introduced ───────────────────────────────────────

    public function test_only_valid_event_types_are_returned(): void
    {
        $validValues = array_column(MatchEventType::cases(), 'value');

        foreach (PitchZone::cases() as $zone) {
            foreach (MatchPhase::cases() as $phase) {
                $candidates = $this->resolver->resolve($zone, $phase);
                foreach ($candidates as $event) {
                    $this->assertContains($event->value, $validValues);
                }
            }
        }
    }

    public function test_candidate_list_is_never_empty(): void
    {
        foreach (PitchZone::cases() as $zone) {
            foreach (MatchPhase::cases() as $phase) {
                $candidates = $this->resolver->resolve($zone, $phase);
                $this->assertNotEmpty($candidates, "Empty candidates for zone={$zone->value} phase={$phase->value}");
            }
        }
    }
}
