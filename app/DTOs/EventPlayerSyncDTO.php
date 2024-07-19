<?php

namespace App\DTOs;

readonly class EventPlayerSyncDTO
{
    public function __construct(
        public string $eventId,
        public string $playerName,
        public bool $isShortPlayerName,
        public int $teamNumber,
        public int $positionNumber,
        public int $bookmaker,
    ) {}
}
