<?php

namespace App\Data;

/** Pre-match context computed once before simulation starts. Immutable. */
readonly class MatchContextData
{
    public function __construct(
        public string                  $fixtureId,
        public string                  $homeTeamId,
        public string                  $awayTeamId,
        public TeamStrengthProfileData $homeProfile,
        public TeamStrengthProfileData $awayProfile,
        public float                   $homeAdvantageFactor,        // 0–1
        public float                   $tempoFactor,                // 0–1, higher = faster game
        public float                   $refStrictnessFactor,        // 0–1, higher = more fouls called
        public float                   $expectedHomeAttackingPressure,
        public float                   $expectedAwayAttackingPressure,
    ) {}
}
