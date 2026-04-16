<?php

namespace App\Data;

/** Normalised (0–1) strength values derived from TeamStat for use inside the simulation. */
readonly class TeamStrengthProfileData
{
    public function __construct(
        public string $teamId,
        public float  $attack,
        public float  $defense,
        public float  $midfield,
        public float  $finishing,
        public float  $goalkeeper,
        public float  $pressing,
        public float  $setPiece,
        public float  $consistency,
        public float  $fatigueResistance,
        public float  $bigMatchPerformance,
        public float  $winnerMentality,   // 0–1 (raw /10)
        public float  $loserMentality,    // 0–1 (raw /10)
        public int    $homeAdvantageRaw,  // raw 1–10 from DB
    ) {}
}
