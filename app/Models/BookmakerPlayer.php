<?php

namespace App\Models;

use App\Enums\Bookmaker;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookmakerPlayer extends Model
{
    use HasFactory;
    use HasUuids;

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
        'bookmaker' => Bookmaker::class,
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
