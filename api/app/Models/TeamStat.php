<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $team_id
 * @property int    $attack
 * @property int    $midfield
 * @property int    $defense
 * @property int    $speed
 * @property int    $pass
 * @property int    $shot
 * @property int    $goalkeeper
 * @property int    $finishing
 * @property int    $chance_creation
 * @property int    $pressing
 * @property int    $set_piece_strength
 * @property int    $winner_mentality
 * @property int    $loser_mentality
 * @property int    $consistency
 * @property int    $discipline
 * @property int    $fatigue_resistance
 * @property int    $big_match_performance
 * @property int    $resilience
 * @property int    $manager_influence
 * @property int    $squad_depth
 * @property int    $injury_risk
 * @property int    $star_players_count
 * @property int    $pot
 * @property int    $home_advantage
 * @property int    $min_temp_performance
 * @property int    $max_temp_performance
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Team $team
 */
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
