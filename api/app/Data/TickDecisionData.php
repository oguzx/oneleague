<?php

namespace App\Data;

use App\Enums\MatchEventType;

/** Result of EventSelector for a single tick: which event was chosen and whether it is visible. */
readonly class TickDecisionData
{
    public function __construct(
        public MatchEventType $event,
        public bool           $isVisible,
    ) {}
}
