<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $teams = Team::whereNotNull('logo_url')
            ->select('id', 'name', 'logo_url')
            ->get();

        return ApiResponse::success($teams);
    }
}
