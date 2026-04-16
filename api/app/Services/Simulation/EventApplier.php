<?php

namespace App\Services\Simulation;

use App\Data\MatchContextData;
use App\Data\MatchEventData;
use App\Data\MatchStateData;
use App\Data\TeamStrengthProfileData;
use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;

/**
 * Applies one event to the match state and returns the MatchEventData objects
 * generated (can be more than one, e.g. shot_attempt + goal in the same tick).
 *
 * Mutates $state in-place.
 *
 * @return MatchEventData[]
 */
class EventApplier
{
    /** @return MatchEventData[] */
    public function apply(
        MatchEventType $event,
        MatchStateData $state,
        MatchContextData $context,
    ): array {
        $events = [];

        $events[] = $this->buildEvent($event, $state);

        match($event) {
            MatchEventType::PassFailed,
            MatchEventType::DribbleFailed,
            MatchEventType::Interception,
            MatchEventType::TackleWon    => $this->handlePossessionChange($state),

            MatchEventType::ShotAttempt  => $events = array_merge(
                $events,
                $this->resolveShotOutcome($state, $context)
            ),

            MatchEventType::CornerWon    => $this->handleCornerWon($state),
            MatchEventType::FoulCommitted => $this->handleFoul($state),
            MatchEventType::Goal          => $this->handleGoal($state),

            MatchEventType::DribbleSuccess => $state->zone = $state->zone->advance(),
            MatchEventType::PassCompleted  => $state->zone = $this->progressZoneOnPass($state->zone),

            MatchEventType::Kickoff,
            MatchEventType::PossessionStart => $state->zone = PitchZone::MiddleThird,

            MatchEventType::HalfTime => $this->handleHalfTime($state, $context),
            MatchEventType::FullTime  => $state->isFinished = true,

            default => null,
        };

        $this->updateFatigue($state, $context);
        $this->updateMomentum($event, $state);

        $state->lastEventType = $event;

        return $events;
    }

    // ─── Possession & zone ────────────────────────────────────────────────────

    private function handlePossessionChange(MatchStateData $state): void
    {
        $state->zone = $state->zone->flipForPossessionChange();
        $state->switchPossession();
        $state->phase = MatchPhase::Normal;
    }

    private function handleCornerWon(MatchStateData $state): void
    {
        $state->zone  = PitchZone::PenaltyArea;
        $state->phase = MatchPhase::CornerKick;
    }

    private function handleFoul(MatchStateData $state): void
    {
        $state->phase = MatchPhase::FreeKick;
        // Possession does not change on a foul; the fouled team keeps it
    }

    private function handleGoal(MatchStateData $state): void
    {
        if ($state->possessionIsHome()) {
            $state->homeScore++;
        } else {
            $state->awayScore++;
        }

        // After a goal the other team kicks off from the centre
        $state->switchPossession();
        $state->zone  = PitchZone::MiddleThird;
        $state->phase = MatchPhase::AfterGoal;
    }

    private function handleHalfTime(MatchStateData $state, MatchContextData $context): void
    {
        $state->currentHalf = 2;
        // Away team kicks off second half
        $state->possessionTeamId = $context->awayTeamId;
        $state->defendingTeamId  = $context->homeTeamId;
        $state->zone             = PitchZone::MiddleThird;
        $state->phase            = MatchPhase::Normal;
    }

    // ─── Shot outcome resolution ─────────────────────────────────────────────

    /** @return MatchEventData[] */
    private function resolveShotOutcome(
        MatchStateData $state,
        MatchContextData $context,
    ): array {
        $attacker  = $state->possessionIsHome() ? $context->homeProfile : $context->awayProfile;
        $keeper    = $state->possessionIsHome() ? $context->awayProfile : $context->homeProfile;
        $outcome   = $this->rollShotOutcome($attacker, $keeper, $state->zone);

        $event = $this->buildEvent($outcome, $state);

        if ($outcome === MatchEventType::Goal) {
            $this->handleGoal($state);
        } else {
            // Keeper / defender wins possession
            $state->zone = $state->zone->flipForPossessionChange();
            $state->switchPossession();
            $state->phase = MatchPhase::Normal;
        }

        return [$event];
    }

    private function rollShotOutcome(
        TeamStrengthProfileData $attacker,
        TeamStrengthProfileData $keeper,
        PitchZone $zone,
    ): MatchEventType {
        $goalProb    = $attacker->finishing * (1.0 - $keeper->goalkeeper * 0.70) * $zone->shotConversionModifier();
        $savedProb   = $keeper->goalkeeper * 0.45;
        $blockedProb = $keeper->defense    * 0.20;

        $roll = mt_rand(0, 1_000_000) / 1_000_000.0;

        return match(true) {
            $roll < $goalProb                            => MatchEventType::Goal,
            $roll < $goalProb + $savedProb               => MatchEventType::ShotSaved,
            $roll < $goalProb + $savedProb + $blockedProb => MatchEventType::ShotBlocked,
            default                                      => MatchEventType::ShotOffTarget,
        };
    }

    // ─── Zone progression ─────────────────────────────────────────────────────

    private function progressZoneOnPass(PitchZone $zone): PitchZone
    {
        $roll = mt_rand(0, 99);

        return match($zone) {
            PitchZone::DefensiveThird  => $roll < 40 ? PitchZone::MiddleThird    : $zone,
            PitchZone::MiddleThird     => match(true) {
                $roll < 30             => PitchZone::AttackingThird,
                $roll < 38             => PitchZone::DefensiveThird,
                default                => $zone,
            },
            PitchZone::AttackingThird  => match(true) {
                $roll < 28             => PitchZone::PenaltyArea,
                $roll < 40             => PitchZone::MiddleThird,
                default                => $zone,
            },
            PitchZone::PenaltyArea     => $roll < 35 ? PitchZone::AttackingThird : $zone,
        };
    }

    // ─── Fatigue & momentum ───────────────────────────────────────────────────

    private function updateFatigue(MatchStateData $state, MatchContextData $context): void
    {
        $rate = $state->currentHalf === 1
            ? MatchConstants::FATIGUE_RATE_FIRST_HALF
            : MatchConstants::FATIGUE_RATE_SECOND_HALF;

        $state->homeFatigue = min(1.0,
            $state->homeFatigue + $rate * (1.0 - $context->homeProfile->fatigueResistance * 0.5)
        );
        $state->awayFatigue = min(1.0,
            $state->awayFatigue + $rate * (1.0 - $context->awayProfile->fatigueResistance * 0.5)
        );
    }

    private function updateMomentum(MatchEventType $event, MatchStateData $state): void
    {
        // Both sides drift toward neutral every tick
        $state->homeMomentum = $state->homeMomentum * MatchConstants::MOMENTUM_DECAY
                             + MatchConstants::MOMENTUM_NEUTRAL * (1.0 - MatchConstants::MOMENTUM_DECAY);
        $state->awayMomentum = $state->awayMomentum * MatchConstants::MOMENTUM_DECAY
                             + MatchConstants::MOMENTUM_NEUTRAL * (1.0 - MatchConstants::MOMENTUM_DECAY);

        $delta = match($event) {
            MatchEventType::Goal        => 0.15,
            MatchEventType::ShotAttempt => 0.03,
            MatchEventType::CornerWon   => 0.02,
            MatchEventType::Interception,
            MatchEventType::TackleWon   => 0.04,
            default                     => 0.0,
        };

        if ($delta === 0.0) return;

        if ($state->possessionIsHome()) {
            $state->homeMomentum = min(1.0, $state->homeMomentum + $delta);
            $state->awayMomentum = max(0.0, $state->awayMomentum - $delta * 0.3);
        } else {
            $state->awayMomentum = min(1.0, $state->awayMomentum + $delta);
            $state->homeMomentum = max(0.0, $state->homeMomentum - $delta * 0.3);
        }
    }

    // ─── Event builder ────────────────────────────────────────────────────────

    private function buildEvent(MatchEventType $type, MatchStateData $state): MatchEventData
    {
        return new MatchEventData(
            type:           $type,
            minute:         $state->currentMinute,
            second:         $state->currentSecond,
            tick:           $state->currentTick,
            teamId:         $this->actingTeamId($type, $state),
            opponentTeamId: $this->opponentTeamId($type, $state),
            zone:           $state->zone,
            payload:        $this->buildPayload($type, $state),
        );
    }

    private function actingTeamId(MatchEventType $type, MatchStateData $state): ?string
    {
        return match($type) {
            MatchEventType::HalfTime, MatchEventType::FullTime => null,
            // Possession-change events are performed by the defending team
            MatchEventType::Interception,
            MatchEventType::TackleWon => $state->defendingTeamId,
            default                   => $state->possessionTeamId,
        };
    }

    private function opponentTeamId(MatchEventType $type, MatchStateData $state): ?string
    {
        return match($type) {
            MatchEventType::HalfTime,
            MatchEventType::FullTime,
            MatchEventType::Kickoff   => null,
            default                   => $state->defendingTeamId,
        };
    }

    private function buildPayload(MatchEventType $type, MatchStateData $state): array
    {
        return match($type) {
            MatchEventType::Goal => [
                'score_after' => [
                    'home' => $state->homeScore,
                    'away' => $state->awayScore,
                ],
            ],
            MatchEventType::ShotAttempt => [
                'zone' => $state->zone->value,
            ],
            default => [],
        };
    }
}
