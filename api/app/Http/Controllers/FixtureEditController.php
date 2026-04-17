<?php

namespace App\Http\Controllers;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixtureEditController extends Controller
{
    public function __invoke(Request $request, Fixture $fixture): JsonResponse
    {
        if ($fixture->status !== FixtureStatus::Completed) {
            return response()->json(['message' => 'Only completed fixtures can be edited.'], 422);
        }

        $data = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $fixture->events()->delete();

        $fixture->update([
            'home_score'         => $data['home_score'],
            'away_score'         => $data['away_score'],
            'is_manually_edited' => true,
            'manually_edited_at' => now(),
        ]);

        return response()->json(['message' => 'Fixture updated.']);
    }
}
