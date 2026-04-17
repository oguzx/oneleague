<?php

namespace App\Data;

use App\Enums\WeatherCondition;

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
        public WeatherCondition        $weather,                    // randomly set once per match
        public float                   $fatigueFactor,              // 1.0 default; >1.0 for heat/snow
    ) {}
}
