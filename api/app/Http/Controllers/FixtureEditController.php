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
        if (!in_array($fixture->status, [FixtureStatus::Scheduled, FixtureStatus::Completed], strict: true)) {
            return ApiResponse::error(
                'Only scheduled or completed fixtures can be edited.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'INVALID_STATE'
            );
        }

        // Delete simulation events only when overwriting a completed match
        if ($fixture->status === FixtureStatus::Completed) {
            $fixture->events()->delete();
        }

        $fixture->update([
            'home_score'         => $request->integer('home_score'),
            'away_score'         => $request->integer('away_score'),
            'status'             => FixtureStatus::Completed,
            'is_manually_edited' => true,
            'manually_edited_at' => now(),
        ]);

        return ApiResponse::success(['message' => 'Fixture updated.']);
    }
}
