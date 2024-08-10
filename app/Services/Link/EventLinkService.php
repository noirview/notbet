<?php

namespace App\Services\Link;

use App\Enums\EventPlayer\PositionNumber;
use App\Enums\EventPlayer\TeamNumber;
use App\Models\Bet;
use App\Models\BookmakerEvent;
use App\Models\Event;
use App\Models\EventPlayer;
use Illuminate\Support\Collection;

class EventLinkService
{
    public function __construct(
        private EventPlayerLinkService $eventPlayerService,
    ) {}

    public function link(string $masterId, Collection $slaveIds)
    {
        $slaveIds = $slaveIds->filter(
            fn(string $slaveId) => $slaveId != $masterId
        );

        if ($slaveIds->count() == 0) {
            return;
        }

        BookmakerEvent::query()
            ->whereIn('event_id', $slaveIds)
            ->update(['event_id' => $masterId]);

        Bet::query()
            ->whereIn('event_id', $slaveIds)
            ->update(['event_id' => $masterId]);

        $eventIds = $slaveIds->merge([$masterId]);
        $eventPlayers = EventPlayer::query()
            ->whereIn('event_id', $eventIds)
            ->get();

        $masterEventPlayers = $eventPlayers->where('event_id', $masterId);
        $slaveEventPlayers = $eventPlayers->whereIn('event_id', $slaveIds);

        foreach (TeamNumber::getValues() as $teamNumber) {
            foreach (PositionNumber::getValues() as $positionNumber) {
                $masterEventPlayer = $masterEventPlayers
                    ->where('team_number', TeamNumber::fromValue($teamNumber))
                    ->firstWhere('position_number', PositionNumber::fromValue($positionNumber));

                if (!$masterEventPlayer) continue;

                $eventPlayers = $slaveEventPlayers
                    ->where('team_number', TeamNumber::fromValue($teamNumber))
                    ->where('position_number', PositionNumber::fromValue($positionNumber));

                if ($eventPlayers->count() < 1) continue;

                $this->eventPlayerService->link(
                    $masterEventPlayer->id,
                    $eventPlayers->pluck('id'),
                );
            }
        }

        Event::query()
            ->whereIn('id', $slaveIds)
            ->delete();
    }
}
