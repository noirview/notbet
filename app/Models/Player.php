<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;
    use HasUuids;

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->using(EventPlayer::class)
            ->withPivot(['team_number', 'position_number']);
    }

    public function bookmakerPlayers(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
