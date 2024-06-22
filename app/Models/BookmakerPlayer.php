<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookmakerPlayer extends Model
{
    use HasUuids, HasUuids, HasFactory;

    protected $fillable = [
        'player_id',
        'name',
        'is_short_name',
        'bookmaker',
    ];

    protected $casts = [
        'player_id' => 'string',
        'name' => 'string',
        'is_short_name' => 'boolean',
        'bookmaker' => 'integer',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
