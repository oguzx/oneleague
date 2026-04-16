<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamStat extends Model
{
    protected $primaryKey = 'team_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'team_id',
        'attack',
        'midfield',
        'defense',
        'speed',
        'pass',
        'shot',
        'goalkeeper',
        'finishing',
        'chance_creation',
        'pressing',
        'set_piece_strength',
        'winner_mentality',
        'loser_mentality',
        'consistency',
        'discipline',
        'fatigue_resistance',
        'big_match_performance',
        'resilience',
        'manager_influence',
        'squad_depth',
        'injury_risk',
        'star_players_count',
        'pot',
        'home_advantage',
        'min_temp_performance',
        'max_temp_performance',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
