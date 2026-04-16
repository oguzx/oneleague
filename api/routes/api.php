<?php

use App\Http\Controllers\DrawController;
use App\Http\Controllers\PlayAllController;
use App\Http\Controllers\PlayMatchController;
use App\Http\Controllers\PlayWeekController;
use App\Http\Controllers\ResetLeagueController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::post('/tournament/draw', DrawController::class);

Route::get('/tournaments', [TournamentController::class, 'index']);
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
Route::post('/tournaments/{tournament}/play-week', PlayWeekController::class);
Route::post('/tournaments/{tournament}/play-all', PlayAllController::class);
Route::post('/tournaments/{tournament}/reset', ResetLeagueController::class);

Route::post('/fixtures/{fixture}/play', PlayMatchController::class);
