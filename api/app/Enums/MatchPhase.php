<?php

namespace App\Enums;

enum MatchPhase: string
{
    case Normal    = 'normal';
    case CornerKick = 'corner_kick';
    case FreeKick  = 'free_kick';
    case AfterGoal = 'after_goal';
}
