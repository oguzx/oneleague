<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string         $id
 * @property string         $name
 * @property string|null    $color
 * @property string|null    $logo_url
 * @property string|null    $country_code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read TeamStat|null                                                  $stat
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group>          $groups
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Fixture>        $homeFixtures
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Fixture>        $awayFixtures
 */
class Team extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function stat(): HasOne
    {
        return $this->hasOne(TeamStat::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }
}
