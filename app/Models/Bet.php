<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bet extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'event_id',
        'type',
        'number_team',
        'number_period',
        'sign',
        'value',
        'coefficient',
        'bookmaker',
    ];

    protected $casts = [
        'type' => 'integer',
        'number_team' => 'integer',
        'number_period' => 'integer',
        'sign' => 'integer',
        'value' => 'decimal',
        'coefficient' => 'decimal',
        'bookmaker' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function surebets(): BelongsToMany
    {
        return $this->belongsToMany(Surebet::class)
            ->using(SurebetBet::class);
    }
}
