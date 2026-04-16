<?php

namespace App\Models;

use App\Enums\MatchEventType;
use App\Enums\PitchZone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'fixture_id',
        'minute',
        'second',
        'tick_number',
        'sequence',
        'team_id',
        'opponent_team_id',
        'event_type',
        'zone',
        'payload',
    ];

    protected $casts = [
        'event_type' => MatchEventType::class,
        'zone'       => PitchZone::class,
        'payload'    => 'array',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function opponentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'opponent_team_id');
    }
}
