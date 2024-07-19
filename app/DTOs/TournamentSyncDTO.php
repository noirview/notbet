<?php

namespace App\DTOs;

readonly class TournamentSyncDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public int    $bookmaker,
    ) {}
}
