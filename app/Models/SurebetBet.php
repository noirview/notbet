<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class SurebetBet extends Pivot
{
    use HasUuids;
    use HasFactory;

    protected $fillable = [
        'surebet_id',
        'bet_id',
    ];

    protected $casts = [
        'surebet_id' => 'string',
        'bet_id' => 'string',
    ];

    public function surebet(): BelongsTo
    {
        return $this->belongsTo(Surebet::class);
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
