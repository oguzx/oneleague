<?php

namespace App\Jobs;

use App\Enums\FixtureStatus;
use App\Enums\SimulationStatus;
use App\Models\Fixture;
use App\Models\Tournament;
use App\Services\TournamentWeekResolver;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

/**
 * Dispatched after a week's batch completes (via ->then() callback).
 * Resolves the next playable week from fixtures and dispatches its batch,
 * or marks the tournament as Completed when no more weeks remain.
 *
 * Cache lock scoped to (tournamentId, completedWeek) prevents double-dispatch
 * if this job is retried or if the batch then-callback fires more than once.
 */
class AdvanceTournamentWeekJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $tournamentId,
        public readonly int    $completedWeek,
    ) {}

    public function handle(TournamentWeekResolver $resolver): void
    {
        $lockKey = "advance_week:{$this->tournamentId}:{$this->completedWeek}";
        $lock    = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            // Another instance is already handling this advancement — skip safely
            return;
        }

        try {
            // Idempotency guard: week must actually be fully completed before advancing
            if (!$resolver->isWeekCompleted($this->tournamentId, $this->completedWeek)) {
                return;
            }

            $nextWeek = $resolver->resolveNextPlayableWeek($this->tournamentId, $this->completedWeek);

            if ($nextWeek === null) {
                Tournament::where('id', $this->tournamentId)->update([
                    'simulation_status'      => SimulationStatus::Completed->value,
                    'simulation_finished_at' => now(),
                ]);
                return;
            }

            $tid      = $this->tournamentId;
            $fixtures = Fixture::query()
                ->join('groups', 'groups.id', '=', 'fixtures.group_id')
                ->where('groups.tournament_id', $tid)
                ->where('fixtures.match_week', $nextWeek)
                ->where('fixtures.status', FixtureStatus::Scheduled->value)
                ->select('fixtures.id')
                ->pluck('fixtures.id');

            if ($fixtures->isEmpty()) {
                // Edge case: week exists but has no playable fixtures — skip to next
                dispatch(new AdvanceTournamentWeekJob($tid, $nextWeek));
                return;
            }

            $jobs = $fixtures->map(fn($id) => new RunSingleFixtureSimulationJob($id))->all();

            $batch = Bus::batch($jobs)
                ->name("tournament:{$tid}:week:{$nextWeek}")
                ->then(fn(Batch $b) => dispatch(new AdvanceTournamentWeekJob($tid, $nextWeek)))
                ->catch(fn(Batch $b, \Throwable $e) => Tournament::where('id', $tid)
                    ->update(['simulation_status' => SimulationStatus::Failed->value]))
                ->allowFailures(false)
                ->dispatch();

            Tournament::where('id', $tid)->update(['simulation_batch_id' => $batch->id]);

        } finally {
            $lock->release();
        }
    }
}
