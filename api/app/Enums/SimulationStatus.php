<?php

namespace App\Enums;

enum SimulationStatus: string
{
    case Idle      = 'idle';
    case Running   = 'running';
    case Completed = 'completed';
    case Failed    = 'failed';
}
