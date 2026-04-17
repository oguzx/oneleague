<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $teams = Team::whereNotNull('logo_url')
            ->select('id', 'name', 'logo_url')
            ->get();

        return response()->json($teams);
    }
}
