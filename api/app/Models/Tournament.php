<?php

namespace App\Models;

use App\Enums\SimulationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string                   $id
 * @property string                   $name
 * @property SimulationStatus|null    $simulation_status
 * @property string|null              $simulation_batch_id
 * @property \Carbon\Carbon|null      $simulation_started_at
 * @property \Carbon\Carbon|null      $simulation_finished_at
 * @property \Carbon\Carbon           $created_at
 * @property \Carbon\Carbon           $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $groups
 */
class Tournament extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'simulation_status'      => SimulationStatus::class,
        'simulation_started_at'  => 'datetime',
        'simulation_finished_at' => 'datetime',
    ];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
