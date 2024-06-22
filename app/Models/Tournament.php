<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;
    use HasUuids;

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function bookmakerTournaments(): HasMany
    {
        return $this->hasMany(BookmakerTournament::class);
    }
}
