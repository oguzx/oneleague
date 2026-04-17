<?php

namespace App\Services\Simulation\Modifiers;

use App\Data\EventWeightBag;
use App\Data\MatchContextData;
use App\Data\MatchStateData;
use App\Enums\MatchEventType;
use App\Enums\WeatherCondition;

class WeatherEventWeightModifier implements EventWeightModifierInterface
{
    public function modify(
        EventWeightBag   $bag,
        MatchStateData   $state,
        MatchContextData $context,
    ): void {
        match($context->weather) {
            WeatherCondition::Rain  => $this->applyRain($bag),
            WeatherCondition::Snow  => $this->applySnow($bag),
            WeatherCondition::Heat  => $this->applyHeat($bag),
            WeatherCondition::Windy => $this->applyWindy($bag),
            WeatherCondition::Foggy => $this->applyFoggy($bag),
            WeatherCondition::Clear => null,
        };
    }

    // Wet surface: passing less accurate, dribbling harder
    private function applyRain(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::PassCompleted,  0.88);
        $bag->scale(MatchEventType::PassFailed,     1.18);
        $bag->scale(MatchEventType::DribbleFailed,  1.15);
    }

    // Slow movement: dribbling and shooting impaired
    private function applySnow(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::DribbleSuccess, 0.82);
        $bag->scale(MatchEventType::DribbleFailed,  1.20);
        $bag->scale(MatchEventType::ShotAttempt,    0.85);
    }

    // Stamina drain handled via fatigueFactor in EventApplier;
    // add a mild foul rate increase for short-tempered tired players
    private function applyHeat(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::FoulCommitted, 1.10);
    }

    // Unpredictable ball flight: passing and shooting disrupted
    private function applyWindy(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::ShotAttempt, 0.88);
        $bag->scale(MatchEventType::PassFailed,  1.12);
        $bag->scale(MatchEventType::CornerWon,   1.08);
    }

    // Reduced visibility: conservative play, fewer shots
    private function applyFoggy(EventWeightBag $bag): void
    {
        $bag->scale(MatchEventType::PassCompleted,  0.90);
        $bag->scale(MatchEventType::PassFailed,     1.12);
        $bag->scale(MatchEventType::ShotAttempt,    0.85);
        $bag->scale(MatchEventType::DribbleSuccess, 0.92);
    }
}
