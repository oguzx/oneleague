<?php

namespace App\Data;

use App\Enums\MatchEventType;
use App\Enums\MatchPhase;
use App\Enums\PitchZone;

/**
 * Mutable live state of the match during simulation.
 * Not persisted directly — final values are written to Fixture after simulation.
 */
class MatchStateData
{
    public int            $currentMinute      = 0;
    public int            $currentSecond      = 0;
    public int            $currentTick        = 0;
    public int            $currentHalf        = 1;
    public string         $homeTeamId;
    public string         $awayTeamId;
    public string         $possessionTeamId;
    public string         $defendingTeamId;
    public PitchZone      $zone               = PitchZone::MiddleThird;
    public int            $homeScore          = 0;
    public int            $awayScore          = 0;
    public MatchPhase     $phase              = MatchPhase::Normal;
    public float          $homeFatigue        = 0.0;  // 0–1
    public float          $awayFatigue        = 0.0;  // 0–1
    public float          $homeMomentum       = 0.5;  // 0–1
    public float          $awayMomentum       = 0.5;  // 0–1
    public ?MatchEventType $lastEventType      = null;
    public bool           $isFinished         = false;

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

    public function switchPossession(): void
    {
        [$this->possessionTeamId, $this->defendingTeamId] =
            [$this->defendingTeamId, $this->possessionTeamId];
    }
}
