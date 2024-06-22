<?php

namespace App\Models;

use App\Enums\Bookmaker;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookmakerTournament extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'external_id',
        'tournament_id',
        'name',
        'bookmaker',
    ];

    protected $casts = [
        'external_id' => 'string',
        'tournament_id' => 'string',
        'name' => 'string',
        'bookmaker' => Bookmaker::class,
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
