<?php

namespace App\Http\Controllers;

use App\Actions\ResetLeagueAction;
use App\Models\Tournament;
use App\Services\TournamentFormatter;
use Illuminate\Http\JsonResponse;

class ResetLeagueController extends Controller
{
    public function __construct(
        private readonly ResetLeagueAction   $action,
        private readonly TournamentFormatter $formatter,
    ) {}

    public function __invoke(Tournament $tournament): JsonResponse
    {
        $this->action->execute($tournament);

        return response()->json($this->formatter->format($tournament));
    }
}
