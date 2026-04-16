<?php

namespace App\Data;

/** One row in a derived league standings table. */
readonly class LeagueTableRowData
{
    public function __construct(
        public string  $teamId,
        public string  $teamName,
        public ?string $logoUrl,
        public int     $played,
        public int    $won,
        public int    $drawn,
        public int    $lost,
        public int    $goalsFor,
        public int    $goalsAgainst,
        public int    $goalDifference,
        public int    $points,
    ) {}
}
