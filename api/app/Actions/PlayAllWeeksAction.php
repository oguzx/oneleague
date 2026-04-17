<?php

namespace App\Actions;

use App\Enums\FixtureStatus;
use App\Enums\SimulationStatus;
use App\Exceptions\InvalidTournamentStateException;
use App\Jobs\AdvanceTournamentWeekJob;
use App\Jobs\RunSingleFixtureSimulationJob;
use App\Models\Fixture;
use App\Models\Tournament;
use App\Services\TournamentWeekResolver;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

/**
 * Entry point for asynchronous full-tournament simulation.
 *
 * Dispatches the first playable week as a Laravel Job Batch and returns
 * immediately. Each week's batch drives the next via AdvanceTournamentWeekJob
 * until no scheduled fixtures remain.
 *
 * Concurrency safety:
 * - Rejects a second start if simulation_status is already Running.
 * - A short-lived cache lock prevents two simultaneous HTTP requests from
 *   both successfully starting the same tournament's simulation.
 */
class PlayAllWeeksAction
{
    public function __construct(private readonly TournamentWeekResolver $resolver) {}

    /** @throws InvalidTournamentStateException */
    public function execute(Tournament $tournament): void
    {
        if ($tournament->simulation_status === SimulationStatus::Running) {
            throw new InvalidTournamentStateException('Simulation is already in progress.');
        }

        $lock = Cache::lock("start_simulation:{$tournament->id}", 10);

        if (!$lock->get()) {
            throw new InvalidTournamentStateException('Simulation is already starting.');
        }

        try {
            $firstWeek = $this->resolver->resolveFirstPlayableWeek($tournament->id);

            if ($firstWeek === null) {
                throw new InvalidTournamentStateException('No scheduled fixtures remaining in this tournament.');
            }

            $tid      = $tournament->id;
            $fixtures = Fixture::query()
                ->where('tournament_id', $tid)
                ->where('match_week', $firstWeek)
                ->where('status', FixtureStatus::Scheduled->value)
                ->pluck('id');

            $jobs = $fixtures->map(fn($id) => new RunSingleFixtureSimulationJob($id))->all();

            $batch = Bus::batch($jobs)
                ->name("tournament:{$tid}:week:{$firstWeek}")
                ->then(fn(Batch $b) => dispatch(new AdvanceTournamentWeekJob($tid, $firstWeek)))
                ->catch(fn(Batch $b, \Throwable $e) => Tournament::where('id', $tid)
                    ->update(['simulation_status' => SimulationStatus::Failed->value]))
                ->allowFailures(false)
                ->dispatch();

            $tournament->update([
                'simulation_status'      => SimulationStatus::Running,
                'simulation_batch_id'    => $batch->id,
                'simulation_started_at'  => now(),
                'simulation_finished_at' => null,
            ]);

        } finally {
            $lock->release();
        }
    }
}
