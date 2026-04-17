<?php

namespace App\Http\Controllers;

use App\Enums\FixtureStatus;
use App\Http\Requests\UpdateFixtureRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Fixture;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FixtureEditController extends Controller
{
    public function __invoke(UpdateFixtureRequest $request, Fixture $fixture): JsonResponse
    {
        if ($fixture->status !== FixtureStatus::Completed) {
            return ApiResponse::error(
                'Only completed fixtures can be edited.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'INVALID_STATE'
            );
        }

        $fixture->events()->delete();

        $fixture->update([
            'home_score'         => $request->integer('home_score'),
            'away_score'         => $request->integer('away_score'),
            'is_manually_edited' => true,
            'manually_edited_at' => now(),
        ]);

        return ApiResponse::success(['message' => 'Fixture updated.']);
    }
}
