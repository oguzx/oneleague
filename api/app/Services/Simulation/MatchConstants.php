<?php

namespace App\Services\Simulation;

final class MatchConstants
{
    public const int DURATION_MINUTES   = 90;
    public const int TICK_SECONDS       = 30;
    public const int TICKS_PER_MINUTE   = 60 / self::TICK_SECONDS;   // 4
    public const int TOTAL_TICKS        = self::DURATION_MINUTES * self::TICKS_PER_MINUTE; // 360
    public const int HALF_TICKS         = self::TOTAL_TICKS / 2;     // 180

    /** Momentum drifts back to neutral by this factor every tick. */
    public const float MOMENTUM_DECAY   = 0.99;
    public const float MOMENTUM_NEUTRAL = 0.5;

    /** Fatigue gained per tick (per half). */
    public const float FATIGUE_RATE_FIRST_HALF  = 0.0020;
    public const float FATIGUE_RATE_SECOND_HALF = 0.0035;
}
