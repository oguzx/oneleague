<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class TeamService
{
    /**
     * @return Collection
     */
    public static function getActiveTeams(?string $relation = null): \Illuminate\Database\Eloquent\Collection
    {
        return Team::with($relation)->get();
    }
}
