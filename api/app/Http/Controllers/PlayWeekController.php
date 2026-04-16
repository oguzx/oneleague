<?php

namespace App\Http\Controllers;

use App\Actions\PlayWeekAction;
use App\Exceptions\InvalidTournamentStateException;
use App\Models\Tournament;
use App\Services\TournamentFormatter;
use Illuminate\Http\JsonResponse;
class PlayWeekController extends Controller
{
    public function __construct(
        private readonly PlayWeekAction      $action,
        private readonly TournamentFormatter $formatter,
    ) {}

    public function __invoke(Tournament $tournament): JsonResponse
    {
        try {
            $this->action->execute($tournament);
        } catch (InvalidTournamentStateException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatter->format($tournament));
    }
}
