<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'tournament_id',
    ];

    protected $casts = [
        'tournament_id' => 'string',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function bookmakerEvents(): HasMany
    {
        return $this->hasMany(BookmakerEvent::class);
    }
}
