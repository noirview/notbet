<?php

namespace App\DTOs;

readonly class BetSyncDTO
{
    public function __construct(
        public string $eventExternalId,
        public int $type,
        public ?int $numberTeam,
        public ?int $numberPeriod,
        public ?int $sign,
        public ?float $value,
        public float $coefficient,
        public int $bookmaker,
    ) {}
}
