<?php

namespace App\Enums;

enum WeatherCondition: string
{
    case Clear = 'clear';
    case Rain  = 'rain';
    case Snow  = 'snow';
    case Heat  = 'heat';
    case Windy = 'windy';
    case Foggy = 'foggy';

    /** Multiplier applied to the per-tick fatigue accumulation rate. */
    public function fatigueFactor(): float
    {
        return match($this) {
            self::Heat  => 1.30,
            self::Snow  => 1.10,
            default     => 1.00,
        };
    }
}
