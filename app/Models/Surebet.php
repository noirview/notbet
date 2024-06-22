<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Surebet extends Model
{
    use HasFactory;
    use HasUuids;

    public function bets(): BelongsToMany
    {
        return $this->belongsToMany(Bet::class)
            ->using(SurebetBet::class);
    }
}
