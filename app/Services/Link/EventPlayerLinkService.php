<?php

namespace App\Services\Link;

use App\Models\EventPlayer;
use Illuminate\Support\Collection;

class EventPlayerLinkService
{
    public function __construct(
        private PlayerLinkService $playerService,
    ) {}

    public function link(string $masterId, Collection $slaveIds)
    {
        $slaveIds = $slaveIds->filter(
            fn(string $slaveId) => $slaveId != $masterId
        );

        if ($slaveIds->count() == 0) {
            return;
        }

        $eventPlayerIds = $slaveIds->push($masterId);
        $eventPlayers = EventPlayer::query()
            ->whereIn('id', $eventPlayerIds)
            ->get();

        $masterEventPlayer = $eventPlayers->firstWhere('id', $masterId);
        $masterPlayerId = $masterEventPlayer->player_id;

        $slaveEventPlayers = $eventPlayers->whereIn('id', $slaveIds);
        $slavePlayerIds = $slaveEventPlayers->pluck('player_id');

        $this->playerService->link($masterPlayerId, $slavePlayerIds);

        EventPlayer::query()
            ->whereIn('id', $slaveIds)
            ->delete();
    }
}
