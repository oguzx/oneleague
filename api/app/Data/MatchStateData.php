<?php

namespace App\Data;

use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;
use App\Services\Simulation\MatchConstants;

/**
 * Mutable live state of the match during simulation.
 * All mutation goes through explicit methods — no public property writes.
 * Not persisted directly; final score is extracted after the loop ends.
 */
class MatchStateData
{
    private int            $currentMinute  = 0;
    private int            $currentSecond  = 0;
    private int            $currentTick    = 0;
    private int            $currentHalf    = 1;
    private PitchZone      $zone           = PitchZone::MiddleThird;
    private int            $homeScore      = 0;
    private int            $awayScore      = 0;
    private MatchPhase     $phase          = MatchPhase::Normal;
    private float          $homeFatigue    = 0.0;
    private float          $awayFatigue    = 0.0;
    private float          $homeMomentum   = 0.5;
    private float          $awayMomentum   = 0.5;
    private ?MatchEventType $lastEventType = null;
    private bool           $isFinished     = false;

    private string $possessionTeamId;
    private string $defendingTeamId;

    public function __construct(
        private readonly string $homeTeamId,
        private readonly string $awayTeamId,
    ) {
        // Home team always kicks off
        $this->possessionTeamId = $homeTeamId;
        $this->defendingTeamId  = $awayTeamId;
    }

    // ─── Getters ─────────────────────────────────────────────────────────────

    public function currentMinute(): int            { return $this->currentMinute; }
    public function currentSecond(): int            { return $this->currentSecond; }
    public function currentTick(): int              { return $this->currentTick; }
    public function currentHalf(): int              { return $this->currentHalf; }
    public function homeTeamId(): string            { return $this->homeTeamId; }
    public function awayTeamId(): string            { return $this->awayTeamId; }
    public function possessionTeamId(): string      { return $this->possessionTeamId; }
    public function defendingTeamId(): string       { return $this->defendingTeamId; }
    public function zone(): PitchZone               { return $this->zone; }
    public function homeScore(): int                { return $this->homeScore; }
    public function awayScore(): int                { return $this->awayScore; }
    public function phase(): MatchPhase             { return $this->phase; }
    public function homeFatigue(): float            { return $this->homeFatigue; }
    public function awayFatigue(): float            { return $this->awayFatigue; }
    public function homeMomentum(): float           { return $this->homeMomentum; }
    public function awayMomentum(): float           { return $this->awayMomentum; }
    public function lastEventType(): ?MatchEventType { return $this->lastEventType; }
    public function isFinished(): bool              { return $this->isFinished; }

    // ─── Derived helpers ─────────────────────────────────────────────────────

    public function possessionIsHome(): bool
    {
        return $this->possessionTeamId === $this->homeTeamId;
    }

    public function possessionFatigue(): float
    {
        return $this->possessionIsHome() ? $this->homeFatigue : $this->awayFatigue;
    }

    public function possessionMomentum(): float
    {
        return $this->possessionIsHome() ? $this->homeMomentum : $this->awayMomentum;
    }

    // ─── Mutation methods ─────────────────────────────────────────────────────

    public function advanceClock(int $tick): void
    {
        $totalSeconds       = $tick * MatchConstants::TICK_SECONDS;
        $this->currentTick  = $tick;
        $this->currentMinute = (int) ($totalSeconds / 60);
        $this->currentSecond = $totalSeconds % 60;
    }

    public function switchPossession(): void
    {
        [$this->possessionTeamId, $this->defendingTeamId] =
            [$this->defendingTeamId, $this->possessionTeamId];
    }

    public function setPossessionTeam(string $teamId): void
    {
        $this->possessionTeamId = $teamId;
    }

    public function setDefendingTeam(string $teamId): void
    {
        $this->defendingTeamId = $teamId;
    }

    public function setZone(PitchZone $zone): void
    {
        $this->zone = $zone;
    }

    public function setPhase(MatchPhase $phase): void
    {
        $this->phase = $phase;
    }

    public function setCurrentHalf(int $half): void
    {
        $this->currentHalf = $half;
    }

    public function incrementHomeScore(): void
    {
        $this->homeScore++;
    }

    public function incrementAwayScore(): void
    {
        $this->awayScore++;
    }

    public function setHomeFatigue(float $value): void
    {
        $this->homeFatigue = $value;
    }

    public function setAwayFatigue(float $value): void
    {
        $this->awayFatigue = $value;
    }

    public function setHomeMomentum(float $value): void
    {
        $this->homeMomentum = $value;
    }

    public function setAwayMomentum(float $value): void
    {
        $this->awayMomentum = $value;
    }

    public function setLastEvent(MatchEventType $type): void
    {
        $this->lastEventType = $type;
    }

    public function markFinished(): void
    {
        $this->isFinished = true;
    }
}
