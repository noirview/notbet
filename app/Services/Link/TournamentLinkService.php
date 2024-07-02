<?php

namespace App\Services\Link;

use App\Models\BookmakerTournament;
use App\Models\Event;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class TournamentLinkService
{
    public function link(string $masterId, Collection $slaveIds)
    {
        $slaveIds = $slaveIds->filter(
            fn(string $slaveId) => $slaveId != $masterId
        );

        if ($slaveIds->count() == 0) {
            return;
        }

        BookmakerTournament::query()
            ->whereIn('tournament_id', $slaveIds)
            ->update(['tournament_id' => $masterId]);

        Event::query()
            ->whereIn('tournament_id', $slaveIds)
            ->update(['tournament_id' => $masterId]);

        Tournament::query()
            ->whereIn('id', $slaveIds)
            ->delete();
    }
}
