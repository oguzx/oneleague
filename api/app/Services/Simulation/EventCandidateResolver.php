<?php

namespace App\Services\Simulation;

use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;

/**
 * Determines which MatchEventType values are valid candidates for the current
 * match state.
 *
 * Candidate selection is driven by:
 *   - PitchZone  — what is physically plausible in this area of the pitch
 *   - MatchPhase — set-piece situations override open-play candidate sets
 *
 * Base weights and modifiers are applied downstream; this class only decides
 * what is possible, not how likely each event is.
 *
 * @return MatchEventType[]
 */
class EventCandidateResolver
{
    /** @return MatchEventType[] */
    public function resolve(PitchZone $zone, MatchPhase $phase): array
    {
        return match($phase) {
            MatchPhase::CornerKick => $this->cornerKickCandidates(),
            MatchPhase::FreeKick   => $this->freeKickCandidates(),
            default                => $this->openPlayCandidates($zone),
        };
    }

    // ─── Set-piece candidate sets ─────────────────────────────────────────────

    /** @return MatchEventType[] */
    private function cornerKickCandidates(): array
    {
        return [
            MatchEventType::ShotAttempt,
            MatchEventType::PassCompleted,
            MatchEventType::FoulCommitted,
            MatchEventType::Interception,
        ];
    }

    /** @return MatchEventType[] */
    private function freeKickCandidates(): array
    {
        return [
            MatchEventType::PassCompleted,
            MatchEventType::ShotAttempt,
            MatchEventType::FoulCommitted,
            MatchEventType::DribbleSuccess,
        ];
    }

    // ─── Open-play candidate set ──────────────────────────────────────────────

    /** @return MatchEventType[] */
    private function openPlayCandidates(PitchZone $zone): array
    {
        $base = [
            MatchEventType::PassCompleted,
            MatchEventType::PassFailed,
            MatchEventType::DribbleSuccess,
            MatchEventType::DribbleFailed,
            MatchEventType::FoulCommitted,
            MatchEventType::Interception,
            MatchEventType::TackleWon,
        ];

        // Shots are not attempted from the defensive third in open play
        return $zone === PitchZone::DefensiveThird
            ? $base
            : [...$base, MatchEventType::ShotAttempt];
    }
}
