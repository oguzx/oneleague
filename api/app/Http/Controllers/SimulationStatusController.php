<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Tournament;
use App\Services\TournamentWeekResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class SimulationStatusController extends Controller
{
    public function __construct(private readonly TournamentWeekResolver $resolver) {}

    public function __invoke(Tournament $tournament): JsonResponse
    {
        $tournament->refresh();

        ['current_week' => $currentWeek, 'total_weeks' => $totalWeeks] =
            $this->resolver->resolveWeekSummary($tournament->id);

        $batch = $tournament->simulation_batch_id
            ? Bus::findBatch($tournament->simulation_batch_id)
            : null;

        return ApiResponse::success([
            'status'       => $tournament->simulation_status,
            'current_week' => $currentWeek,
            'total_weeks'  => $totalWeeks,
            'batch'        => $batch ? [
                'total_jobs'   => $batch->totalJobs,
                'pending_jobs' => $batch->pendingJobs,
                'failed_jobs'  => $batch->failedJobs,
                'progress'     => $batch->progress(),
            ] : null,
            'started_at'  => $tournament->simulation_started_at,
            'finished_at' => $tournament->simulation_finished_at,
        ]);
    }
}
