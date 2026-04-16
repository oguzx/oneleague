<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
