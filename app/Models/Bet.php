<?php

namespace App\Models;

use App\Enums\Bet\NumberPeriod;
use App\Enums\Bet\NumberTeam;
use App\Enums\Bet\Sign;
use App\Enums\Bet\Type;
use App\Enums\Bookmaker;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'type' => Type::class,
        'number_team' => NumberTeam::class,
        'number_period' => NumberPeriod::class,
        'sign' => Sign::class,
        'value' => 'decimal',
        'coefficient' => 'decimal',
        'bookmaker' => Bookmaker::class,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function surebets(): BelongsToMany
    {
        return $this->belongsToMany(Surebet::class)
            ->using(SurebetBet::class);
    }
}
