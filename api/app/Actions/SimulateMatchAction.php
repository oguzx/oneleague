<?php

namespace App\Actions;

use App\Data\MatchContextData;
use App\Data\MatchEventData;
use App\Data\MatchStateData;
use App\Data\SimulationResultData;
use App\Enums\MatchEventType;
use App\Services\Simulation\EventApplier;
use App\Services\Simulation\EventSelector;
use App\Services\Simulation\MatchConstants;

/**
 * Pure simulation loop — no database writes.
 * Runs 360 ticks (15-second intervals across 90 minutes) and returns
 * a SimulationResultData with the final score and visible timeline events.
 */
class SimulateMatchAction
{
    public function __construct(
        private readonly EventSelector $selector,
        private readonly EventApplier  $applier,
    ) {}

    public function execute(MatchContextData $context, MatchStateData $state): SimulationResultData
    {
        $timeline = $this->runKickoff($state, $context);

        for ($tick = 1; $tick <= MatchConstants::TOTAL_TICKS; $tick++) {
            $state->advanceClock($tick);

            if ($tick === MatchConstants::HALF_TICKS + 1) {
                $events = $this->applier->apply(MatchEventType::HalfTime, $state, $context);
                foreach (array_filter($events, fn($e) => $e->type->isVisible()) as $e) {
                    $timeline[] = $e;
                }
            }

            $decision = $this->selector->select($state, $context);
            $produced = $this->applier->apply($decision->event, $state, $context);

            if ($decision->isVisible) {
                foreach (array_filter($produced, fn($e) => $e->type->isVisible()) as $e) {
                    $timeline[] = $e;
                }
            }
        }

        $fullTime = $this->applier->apply(MatchEventType::FullTime, $state, $context);
        $timeline = array_merge($timeline, $fullTime);

        return new SimulationResultData(
            fixtureId:  $context->fixtureId,
            homeTeamId: $context->homeTeamId,
            awayTeamId: $context->awayTeamId,
            homeScore:  $state->homeScore(),
            awayScore:  $state->awayScore(),
            events:     $timeline,
            finalState: $state,
            weather:    $context->weather,
        );
    }

    /** @return MatchEventData[] */
    private function runKickoff(MatchStateData $state, MatchContextData $context): array
    {
        return $this->applier->apply(MatchEventType::Kickoff, $state, $context);
    }
}
