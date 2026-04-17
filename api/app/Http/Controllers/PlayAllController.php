<?php

namespace App\Http\Controllers;

use App\Actions\PlayAllWeeksAction;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;

class PlayAllController extends Controller
{
    public function __construct(private readonly PlayAllWeeksAction $action) {}

    public function __invoke(Tournament $tournament): JsonResponse
    {
        try {
            $this->action->execute($tournament);
        } catch (InvalidTournamentStateException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Simulation started.'], 202);
    }
}
