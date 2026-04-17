<?php

namespace App\Jobs;

use App\Actions\PlayMatchAction;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunSingleFixtureSimulationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $fixtureId) {}

    public function handle(PlayMatchAction $action): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $fixture = Fixture::findOrFail($this->fixtureId);

        // Idempotency: skip if already simulated (safe on retry)
        if ($fixture->status === FixtureStatus::Completed) {
            return;
        }

        $action->execute($fixture);
    }
}
