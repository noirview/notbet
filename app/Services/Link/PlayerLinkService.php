<?php

namespace App\Services\Link;

use App\Models\BookmakerPlayer;
use App\Models\Player;
use Illuminate\Support\Collection;

class PlayerLinkService
{
    public function link(string $masterId, Collection $slaveIds)
    {
        $slaveIds = $slaveIds->filter(
            fn(string $slaveId) => $slaveId != $masterId
        );

        if ($slaveIds->count() == 0) {
            return;
        }

        BookmakerPlayer::query()
            ->whereIn('player_id', $slaveIds)
            ->update(['player_id' => $masterId]);

        Player::query()
            ->whereIn('id', $slaveIds)
            ->delete();
    }
}
