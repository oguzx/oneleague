<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Tournament;

class GroupService
{
    public function createGroup(Tournament $tournament, string $name): Group
    {
        return $tournament->groups()->create(['name' => $name]);
    }
}
