<?php

namespace App\Http\Controllers;

use App\Actions\PlayAllWeeksAction;
use App\Exceptions\InvalidTournamentStateException;
use App\Http\Responses\ApiResponse;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PlayAllController extends Controller
{
    public function __construct(private readonly PlayAllWeeksAction $action) {}

    public function __invoke(Tournament $tournament): JsonResponse
    {
        try {
            $this->action->execute($tournament);
        } catch (InvalidTournamentStateException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'SIMULATION_ALREADY_RUNNING'
            );
        }

        return ApiResponse::success(
            ['message' => 'Simulation started.'],
            Response::HTTP_ACCEPTED
        );
    }
}
