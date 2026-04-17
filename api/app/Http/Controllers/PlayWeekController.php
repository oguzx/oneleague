<?php

namespace App\Http\Controllers;

use App\Actions\PlayWeekAction;
use App\Exceptions\InvalidTournamentStateException;
use App\Http\Responses\ApiResponse;
use App\Models\Tournament;
use App\Services\TournamentFormatter;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'INVALID_STATE'
            );
        }

        return ApiResponse::success($this->formatter->format($tournament));
    }
}
