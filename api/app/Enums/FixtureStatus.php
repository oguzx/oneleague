<?php

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Live      = 'live';
    case Completed = 'completed';
    case Postponed = 'postponed';
}
