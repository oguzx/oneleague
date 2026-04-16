<?php

use App\Http\Controllers\DrawController;
use Illuminate\Support\Facades\Route;

Route::post('/tournament/draw', DrawController::class);
