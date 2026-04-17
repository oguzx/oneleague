<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string         $id
 * @property string         $tournament_id
 * @property string         $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Tournament                                                    $tournament
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Team>          $teams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Fixture>       $fixtures
 */
class Group extends Model
{
    use HasUuids;

    protected $fillable = ['tournament_id', 'name'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
