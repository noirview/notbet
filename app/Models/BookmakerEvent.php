<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookmakerEvent extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'external_id',
        'event_id',
        'start_at',
        'bookmaker',
    ];

    protected $casts = [
        'external_id' => 'string',
        'event_id' => 'string',
        'start_at' => 'datetime:Y-m-d H:i:s',
        'bookmaker' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
