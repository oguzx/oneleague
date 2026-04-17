<?php

namespace App\Models;

use App\Enums\SimulationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
