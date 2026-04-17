<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidTournamentStateException;
use App\Http\Responses\ApiResponse;
use App\Services\DrawFormatter;
use App\Services\DrawService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DrawController extends Controller
{
    public function __construct(
        private readonly DrawService    $drawService,
        private readonly DrawFormatter  $formatter,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $tournament = $this->drawService->draw();
        } catch (InvalidTournamentStateException $e) {
            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'INVALID_STATE'
            );
        }

        return ApiResponse::success(
            $this->formatter->format($tournament),
            Response::HTTP_CREATED
        );
    }
}
