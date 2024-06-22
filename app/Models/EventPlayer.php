<?php

namespace App\Models;

use App\Enums\EventPlayer\PositionNumber;
use App\Enums\EventPlayer\TeamNumber;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventPlayer extends Pivot
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'player_id',
        'team_number',
        'position_number',
    ];

    protected $casts = [
        'event_id' => 'string',
        'player_id' => 'string',
        'team_number' => TeamNumber::class,
        'position_number' => PositionNumber::class,
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
