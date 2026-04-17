<?php

namespace App\Providers;

use App\Services\Simulation\EventWeightModifierPipeline;
use App\Services\Simulation\Modifiers\FatigueEventWeightModifier;
use App\Services\Simulation\Modifiers\LastEventContextWeightModifier;
use App\Services\Simulation\Modifiers\MomentumEventWeightModifier;
use App\Services\Simulation\Modifiers\StatEventWeightModifier;
use App\Services\Simulation\Modifiers\WeatherEventWeightModifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerModifierPipeline();
    }

    public function boot(): void {}

    /**
     * Register the ordered modifier pipeline for event probability resolution.
     *
     * Order rationale:
     *
     *   1. LastEventContextWeightModifier
     *      Sets phase-specific context first (corner kicks, free kicks) so
     *      the subsequent modifiers work on a contextually-aware baseline.
     *
     *   2. WeatherEventWeightModifier
     *      Applies match-long environmental penalties (rain, snow, heat, etc.)
     *      before team-stat adjustments. Weather is a structural condition that
     *      sets the surface all other modifiers build on top of.
     *
     *   3. StatEventWeightModifier
     *      Applies relative team-strength adjustments on top of context and weather.
     *
     *   4. FatigueEventWeightModifier
     *      Degrades performance based on accumulated tiredness. In hot/snowy
     *      conditions fatigue builds faster (via fatigueFactor), so this modifier
     *      naturally amplifies weather's stamina effects over time.
     *
     *   5. MomentumEventWeightModifier
     *      Applies the final push or drag from match rhythm. Most volatile factor,
     *      sits on top of all structural adjustments as a late-game amplifier.
     */
    private function registerModifierPipeline(): void
    {
        $this->app->bind(EventWeightModifierPipeline::class, fn($app) => new EventWeightModifierPipeline([
            $app->make(LastEventContextWeightModifier::class),
            $app->make(WeatherEventWeightModifier::class),
            $app->make(StatEventWeightModifier::class),
            $app->make(FatigueEventWeightModifier::class),
            $app->make(MomentumEventWeightModifier::class),
        ]));
    }
}
