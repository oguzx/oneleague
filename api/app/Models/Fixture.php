<?php

namespace App\Models;

use App\Enums\FixtureStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fixture extends Model
{
    use HasUuids;

    protected $fillable = [
        'group_id',
        'home_team_id',
        'away_team_id',
        'match_week',
        'status',
        'home_score',
        'away_score',
        'is_manually_edited',
        'manually_edited_at',
    ];

    protected $casts = [
        'status'             => FixtureStatus::class,
        'is_manually_edited' => 'boolean',
        'manually_edited_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class)->orderBy('tick_number')->orderBy('sequence');
    }

    public function isPlayable(): bool
    {
        return $this->status === FixtureStatus::Scheduled;
    }
}
