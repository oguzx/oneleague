<?php

namespace App\Providers;

use App\Services\Simulation\EventWeightModifierPipeline;
use App\Services\Simulation\Modifiers\FatigueEventWeightModifier;
use App\Services\Simulation\Modifiers\LastEventContextWeightModifier;
use App\Services\Simulation\Modifiers\MomentumEventWeightModifier;
use App\Services\Simulation\Modifiers\StatEventWeightModifier;
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
     *      A corner-kick context should inflate shot weights before team stats
     *      apply, not the other way around.
     *
     *   2. StatEventWeightModifier
     *      Applies relative team-strength adjustments on top of the context.
     *      A stronger attacking team should be more likely to convert the
     *      corner opportunity that step 1 already amplified.
     *
     *   3. FatigueEventWeightModifier
     *      Degrades performance based on accumulated tiredness. Applied after
     *      stats because fatigue represents in-match wear on top of a team's
     *      natural ability, not instead of it.
     *
     *   4. MomentumEventWeightModifier
     *      Applies the final push or drag from match rhythm. Momentum is the
     *      most volatile factor and should sit on top of all structural
     *      adjustments as a late-game amplifier.
     */
    private function registerModifierPipeline(): void
    {
        $this->app->bind(EventWeightModifierPipeline::class, fn() => new EventWeightModifierPipeline([
            new LastEventContextWeightModifier(),
            new StatEventWeightModifier(),
            new FatigueEventWeightModifier(),
            new MomentumEventWeightModifier(),
        ]));
    }
}
