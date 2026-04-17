<?php

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Live      = 'live';
    case Completed = 'completed';
    case Postponed = 'postponed';

    /**
     * Valid state transitions:
     *   Scheduled → Completed  (normal simulation flow)
     *   Scheduled → Live       (future live-match support)
     *   Live      → Completed
     *   All other transitions are illegal.
     */
    public function canTransitionTo(FixtureStatus $new): bool
    {
        return match($this) {
            self::Scheduled => in_array($new, [self::Completed, self::Live], strict: true),
            self::Live      => $new === self::Completed,
            self::Completed,
            self::Postponed => false,
        };
    }
}
