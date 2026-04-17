<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidTournamentStateException;
use App\Http\Responses\ApiResponse;
use App\Models\Tournament;
use App\Services\DrawFormatter;
use App\Services\DrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RegenerateLeagueController extends Controller
{
    public function __construct(
        private readonly DrawService   $drawService,
        private readonly DrawFormatter $formatter,
    ) {}

    public function __invoke(Tournament $tournament): JsonResponse
    {
        try {
            $newTournament = DB::transaction(function () use ($tournament) {
                $tournament->delete();
                return $this->drawService->draw();
            });
        } catch (InvalidTournamentStateException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'INVALID_STATE'
            );
        }

        return ApiResponse::success(
            $this->formatter->format($newTournament),
            Response::HTTP_CREATED
        );
    }
}
