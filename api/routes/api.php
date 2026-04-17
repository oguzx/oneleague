<?php

use App\Http\Controllers\DrawController;
use App\Http\Controllers\FixtureEditController;
use App\Http\Controllers\PlayAllController;
use App\Http\Controllers\PlayMatchController;
use App\Http\Controllers\PlayWeekController;
use App\Http\Controllers\ResetLeagueController;
use App\Http\Controllers\SimulationStatusController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

// ── Teams ──────────────────────────────────────────────────────────────────
Route::get('/teams', [TeamController::class, 'index']);

// ── Draw ───────────────────────────────────────────────────────────────────
Route::post('/tournament/draw', DrawController::class);

// ── Tournaments ────────────────────────────────────────────────────────────
Route::prefix('/tournaments/{tournament}')->group(function () {
    Route::get('/',                  [TournamentController::class, 'show']);
    Route::post('/play-week',        PlayWeekController::class);
    Route::post('/play-all',         PlayAllController::class);
    Route::get('/simulation-status', SimulationStatusController::class);
    Route::post('/reset',            ResetLeagueController::class);
});

Route::get('/tournaments', [TournamentController::class, 'index']);

// ── Fixtures ───────────────────────────────────────────────────────────────
Route::prefix('/fixtures/{fixture}')->group(function () {
    Route::post('/play', PlayMatchController::class);
    Route::put('/',      FixtureEditController::class);
});

Route::get('/health', function() {
    return response()->json([
        'health' => 'Fenerbahce!'
    ]);
});
