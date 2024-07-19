<?php

namespace App\DTOs;

readonly class PlayerSyncDTO
{
    public function __construct(
        public string $name,
        public bool $is_short_name,
        public int $bookmaker,
    ) {}
}
