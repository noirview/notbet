<?php

namespace App\Services\Sync;

use App\Contracts\SyncSourceContract;
use App\DTOs\EventPlayerSyncDTO;
use App\Enums\Bookmaker;
use App\Models\BookmakerEvent;
use App\Models\BookmakerPlayer;
use App\Models\EventPlayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventPlayerSyncService extends SyncServiceDecorator
{
    private function createBookmakerEventFromDTO(EventPlayerSyncDTO $dto): BookmakerEvent
    {
        return new BookmakerEvent([
            'external_id' => $dto->eventId,
            'bookmaker' => $dto->bookmaker,
        ]);
    }

    private function createBookmakerPlayerFromDTO(EventPlayerSyncDTO $dto): BookmakerPlayer
    {
        return new BookmakerPlayer([
            'name' => $dto->playerName,
            'is_short_name' => $dto->isShortPlayerName,
            'bookmaker' => $dto->bookmaker,
        ]);
    }

    private function createBookmakerEventUniqueKey(BookmakerEvent $event): string
    {
        return "$event->external_id|$event->bookmaker";
    }

    private function createBookmakerPlayerUniqueKey(BookmakerPlayer $player): string
    {
        $isShortName = $player->is_short_name ? 't' : 'f';

        return "$player->name|$isShortName|$player->bookmaker";
    }

    private function createEventPlayerUniqueKey(EventPlayer $eventPlayer): string
    {
        return "$eventPlayer->event_id|$eventPlayer->player_id|$eventPlayer->team_number|$eventPlayer->position_number";
    }

    private function receiveExistBookmakerEventsByUniqueKeys(Collection $uniqueKeys): Collection
    {
        return BookmakerEvent::query()
            ->whereIn(DB::raw("concat(external_id, '|', bookmaker)"), $uniqueKeys)
            ->get();
    }

    private function receiveExistBookmakerPlayersByUniqueKeys(Collection $uniqueKeys): Collection
    {
        return BookmakerPlayer::query()
            ->whereIn(DB::raw("concat(name, '|', is_short_name, '|', bookmaker)"), $uniqueKeys)
            ->get();
    }

    private function fillEventPlayer(EventPlayer $eventPlayer): EventPlayer
    {
        $eventPlayer->id = Str::uuid();

        $eventPlayer->updated_at = now();
        $eventPlayer->created_at = now();

        return $eventPlayer;
    }

    public function createOrUpdateMany(Collection $eventPlayers): void
    {
        $bookmakerEvents = $eventPlayers
            ->map(fn(EventPlayerSyncDTO $dto) => $this->createBookmakerEventFromDTO($dto));

        $bookmakerPlayers = $eventPlayers
            ->map(fn(EventPlayerSyncDTO $dto) => $this->createBookmakerPlayerFromDTO($dto));

        $bookmakerEventUniqueKeys = $bookmakerEvents->map(
            fn(BookmakerEvent $event) => $this->createBookmakerEventUniqueKey($event)
        );

        $bookmakerPlayerUniqueKeys = $bookmakerPlayers->map(
            fn(BookmakerPlayer $player) => $this->createBookmakerPlayerUniqueKey($player)
        );

        $existsBookmakerEvents = $this->receiveExistBookmakerEventsByUniqueKeys($bookmakerEventUniqueKeys);
        $existsBookmakerPlayers = $this->receiveExistBookmakerPlayersByUniqueKeys($bookmakerPlayerUniqueKeys);

        $bookmakerEvents = $existsBookmakerEvents->filter(fn(BookmakerEvent $event) => $bookmakerEvents
            ->contains(function (BookmakerEvent $existEvent) use ($event) {
                return $this->createBookmakerEventUniqueKey($event) != $this->createBookmakerEventUniqueKey($existEvent);
            })
        );

        $bookmakerPlayers = $existsBookmakerPlayers->filter(fn(BookmakerPlayer $player) => $bookmakerPlayers
            ->contains(function (BookmakerPlayer $existPlayer) use ($player) {
                return $this->createBookmakerPlayerUniqueKey($player) != $this->createBookmakerPlayerUniqueKey($existPlayer);
            })
        );

        $eventPlayers = $eventPlayers->map(function (EventPlayerSyncDTO $dto) use ($bookmakerEvents, $bookmakerPlayers) {
            $bookmakerEvent = $bookmakerEvents
                ->where('external_id', $dto->eventId)
                ->firstWhere('bookmaker', Bookmaker::fromValue($dto->bookmaker));

            $bookmakerPlayer = $bookmakerPlayers
                ->where('name', $dto->playerName)
                ->where('is_short_name', $dto->isShortPlayerName)
                ->firstWhere('bookmaker', Bookmaker::fromValue($dto->bookmaker));

            if ($bookmakerEvent === null || $bookmakerPlayer === null) {
                return null;
            }

            $eventPlayer = new EventPlayer([
                'event_id' => $bookmakerEvent->event_id,
                'player_id' => $bookmakerPlayer->player_id,
                'team_number' => $dto->teamNumber,
                'position_number' => $dto->positionNumber,
            ]);

            return $eventPlayer;
        })->filter();

        $eventPlayerUniqueKeys = $eventPlayers->map(
            fn(EventPlayer $eventPlayer) => $this->createEventPlayerUniqueKey($eventPlayer)
        );

        $existsEventPlayers = EventPlayer::query()
            ->whereIn(DB::raw("concat(event_id, '|', player_id, '|', team_number, '|', position_number)"), $eventPlayerUniqueKeys)
            ->get();

        $eventPlayers = $eventPlayers->filter(fn(EventPlayer $eventPlayer) => !$existsEventPlayers
            ->contains(function (EventPlayer $existsEventPlayer) use ($eventPlayer) {
                return $this->createEventPlayerUniqueKey($eventPlayer) == $this->createEventPlayerUniqueKey($existsEventPlayer);
            })
        )->values();

        $eventPlayers = $eventPlayers->map(
            fn(EventPlayer $eventPlayer) => $this->fillEventPlayer($eventPlayer)
        );

        EventPlayer::query()->insert($eventPlayers->toArray());
    }

    public function deleteExceptMany(Collection $eventPlayers): void
    {
        $bookmakerEvents = $eventPlayers->map(
            fn(EventPlayerSyncDTO $dto) => $this->createBookmakerEventFromDTO($dto)
        );

        $bookmakerEventUniqueKeys = $bookmakerEvents->map(
            fn(BookmakerEvent $event) => $this->createBookmakerEventUniqueKey($event)
        );

        $bookmakerEvents = BookmakerEvent::query()
            ->whereIn(DB::raw("concat(external_id, '|', bookmaker)"), $bookmakerEventUniqueKeys)
            ->get();

        $bookmakerPlayers = $eventPlayers->map(
            fn(EventPlayerSyncDTO $dto) => $this->createBookmakerPlayerFromDTO($dto)
        );

        $bookmakerPlayerUniqueKeys = $bookmakerPlayers->map(
            fn(BookmakerPlayer $player) => $this->createBookmakerPlayerUniqueKey($player)
        );

        $bookmakerPlayers = BookmakerPlayer::query()
            ->whereIn(DB::raw("concat(name, '|', is_short_name, '|', bookmaker)"), $bookmakerPlayerUniqueKeys)
            ->get();

        $eventPlayers = $eventPlayers->map(function (EventPlayerSyncDTO $dto) use ($bookmakerEvents, $bookmakerPlayers) {
            $bookmakerEvent = $bookmakerEvents
                ->where('external_id', $dto->eventId)
                ->firstWhere('bookmaker', Bookmaker::fromValue($dto->bookmaker));

            $bookmakerPlayer = $bookmakerPlayers
                ->where('name', $dto->playerName)
                ->where('is_short_name', $dto->isShortPlayerName)
                ->firstWhere('bookmaker', Bookmaker::fromValue($dto->bookmaker));

            if ($bookmakerEvent === null || $bookmakerPlayer === null) {
                return null;
            }

            $eventPlayer = new EventPlayer([
                'event_id' => $bookmakerEvent->event_id,
                'player_id' => $bookmakerPlayer->player_id,
                'team_number' => $dto->teamNumber,
                'position_number' => $dto->positionNumber,
            ]);

            return $eventPlayer;
        })->filter();

        $eventPlayerUniqueKeys = $eventPlayers->map(
            fn(EventPlayer $eventPlayer) => $this->createEventPlayerUniqueKey($eventPlayer)
        );

        $eventPlayers = EventPlayer::query()
            ->whereIn(DB::raw("concat(event_id, '|', player_id, '|', team_number, '|', position_number)"), $eventPlayerUniqueKeys)
            ->get();

        EventPlayer::query()
            ->whereNotIn('id', $eventPlayers->pluck('id'))
            ->delete();
    }

    public function sync(SyncSourceContract $source): void
    {
        $eventPlayers = $source->eventPlayers();

        $this->createOrUpdateMany($eventPlayers);
        $this->wrappee->sync($source);
        $this->deleteExceptMany($eventPlayers);
    }
}
