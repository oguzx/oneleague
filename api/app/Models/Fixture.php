<?php

namespace App\Models;

use App\Enums\FixtureStatus;
use App\Enums\WeatherCondition;
use App\Exceptions\InvalidTournamentStateException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string          $id
 * @property string          $tournament_id
 * @property string          $group_id
 * @property string          $home_team_id
 * @property string          $away_team_id
 * @property int             $match_week
 * @property FixtureStatus   $status
 * @property int|null        $home_score
 * @property int|null        $away_score
 * @property bool            $is_manually_edited
 * @property \Carbon\Carbon|null $manually_edited_at
 * @property \App\Enums\WeatherCondition|null $weather
 * @property \Carbon\Carbon  $created_at
 * @property \Carbon\Carbon  $updated_at
 *
 * @property-read Group                                                          $group
 * @property-read Team                                                           $homeTeam
 * @property-read Team                                                           $awayTeam
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MatchEvent>     $events
 */
class Fixture extends Model
{
    use HasUuids;

    protected $fillable = [
        'tournament_id',
        'group_id',
        'home_team_id',
        'away_team_id',
        'match_week',
        'status',
        'home_score',
        'away_score',
        'is_manually_edited',
        'manually_edited_at',
        'weather',
    ];

    protected $casts = [
        'status'             => FixtureStatus::class,
        'weather'            => WeatherCondition::class,
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

    /**
     * Transition fixture to a new status, enforcing allowed transitions.
     *
     * @throws InvalidTournamentStateException
     */
    public function transitionTo(FixtureStatus $new): void
    {
        if (!$this->status->canTransitionTo($new)) {
            throw new InvalidTournamentStateException(
                "Cannot transition fixture from [{$this->status->value}] to [{$new->value}]."
            );
        }

        $this->update(['status' => $new]);
    }
}
