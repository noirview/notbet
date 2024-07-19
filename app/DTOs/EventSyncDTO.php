<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class EventSyncDTO
{
    public function __construct(
        public string $id,
        public string $tournament_id,
        public Carbon $start_at,
        public int $bookmaker,
    ) {}
}
