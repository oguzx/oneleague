<?php

namespace App\Data;

use App\Enums\WeatherCondition;

/** Output of SimulateMatchAction. Contains everything needed to persist the result. */
readonly class SimulationResultData
{
    /**
     * @param  MatchEventData[]  $events  All visible timeline events.
     */
    public function __construct(
        public string           $fixtureId,
        public string           $homeTeamId,
        public string           $awayTeamId,
        public int              $homeScore,
        public int              $awayScore,
        public array            $events,
        public MatchStateData   $finalState,
        public WeatherCondition $weather,
    ) {}
}
