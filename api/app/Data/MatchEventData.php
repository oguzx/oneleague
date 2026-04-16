<?php

namespace App\Data;

use App\Enums\MatchEventType;
use App\Enums\PitchZone;

/** A single event in the match timeline. Immutable. */
readonly class MatchEventData
{
    public function __construct(
        public MatchEventType $type,
        public int            $minute,
        public int            $second,
        public int            $tick,
        public ?string        $teamId,
        public ?string        $opponentTeamId,
        public PitchZone      $zone,
        public array          $payload        = [],
    ) {}
}
