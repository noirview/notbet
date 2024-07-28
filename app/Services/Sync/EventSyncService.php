<?php

namespace App\Services\Sync;

use App\Contracts\SyncServiceContract;
use App\Contracts\SyncSourceContract;
use App\DTOs\EventSyncDTO;
use App\Enums\Bookmaker;
use App\Models\BookmakerEvent;
use App\Models\BookmakerTournament;
use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventSyncService extends SyncServiceDecorator
{
    private function createBookmakerTournamentFromDTO(EventSyncDTO $dto): BookmakerTournament
    {
        return new BookmakerTournament([
            'external_id' => $dto->tournament_id,
            'bookmaker' => $dto->bookmaker,
        ]);
    }

    private function createBookmakerEventFromDTO(EventSyncDTO $dto): BookmakerEvent
    {
        return new BookmakerEvent([
            'external_id' => $dto->id,
            'start_at' => $dto->start_at,
            'bookmaker' => $dto->bookmaker,
        ]);
    }

    private function createBookmakerTournamentUniqueKey(BookmakerTournament $tournament): string
    {
        return "$tournament->external_id|$tournament->bookmaker";
    }

    private function createBookmakerEventUniqueKey(BookmakerEvent $event): string
    {
        return "$event->external_id|$event->bookmaker";
    }

    private function receiveExistsBookmakerTournamentsByUniqueKey(Collection $uniqueKeys): Collection
    {
        return BookmakerTournament::query()
            ->whereIn(DB::raw("concat(external_id, '|', bookmaker)"), $uniqueKeys)
            ->get();
    }

    private function receiveExistsBookmakerEventsByUniqueKey(Collection $uniqueKeys): Collection
    {
        return BookmakerEvent::query()
            ->whereIn(DB::raw("concat(external_id, '|', bookmaker)"), $uniqueKeys)
            ->get();
    }

    private function receiveBookmakerEvents(Collection $bookmakerEvents): Collection
    {
        $bookmakerEventUniqueKeys = $bookmakerEvents->map(
            fn(BookmakerEvent $event) => $this->createBookmakerEventUniqueKey($event)
        );

        $bookmakerEvents = $this->receiveExistsBookmakerEventsByUniqueKey($bookmakerEventUniqueKeys);

        return $bookmakerEvents;
    }

    private function receiveBookmakerTournaments(Collection $bookmakerTournaments): Collection
    {
        $bookmakerTournamentUniqueKeys = $bookmakerTournaments->map(
            fn(BookmakerTournament $tournament) => $this->createBookmakerTournamentUniqueKey($tournament)
        );

        $bookmakerTournaments = $this->receiveExistsBookmakerTournamentsByUniqueKey($bookmakerTournamentUniqueKeys);

        return $bookmakerTournaments;
    }

    private function fillBookmakerEvent(BookmakerEvent $event): BookmakerEvent
    {
        $event->id = Str::uuid()->toString();

        $event->created_at = now();
        $event->updated_at = now();

        return $event;
    }

    private function fillEvent(Event $event): Event
    {
        $event->id = Str::uuid()->toString();

        $event->created_at = now();
        $event->updated_at = now();

        return $event;
    }

    private function deleteExceptBookmakerEvents(Collection $bookmakerEvents): void
    {
        $bookmakerEventIds = $bookmakerEvents->pluck('id');

        BookmakerEvent::query()
            ->whereNotIn('id', $bookmakerEventIds)
            ->delete();
    }

    private function deleteExceptEventsFromBookmakerEvents(Collection $bookmakerEvents): void
    {
        $eventIds = $bookmakerEvents->pluck('event_id');

        Event::query()
            ->doesntHave('bookmakerEvents')
            ->whereNotIn('id', $eventIds)
            ->delete();
    }

    public function createOrUpdateMany(Collection $events): void
    {
        $bookmakerTournaments = $events->map(
            fn(EventSyncDTO $dto) => $this->createBookmakerTournamentFromDTO($dto)
        );

        $bookmakerTournaments = $this->receiveBookmakerTournaments($bookmakerTournaments);

        $bookmakerEvents = $events
            ->map(fn(EventSyncDTO $dto) => $this->createBookmakerEventFromDTO($dto));

        $existsBookmakerEvents = $this->receiveBookmakerEvents($bookmakerEvents);

        $bookmakerEvents = $bookmakerEvents->filter(
            fn(BookmakerEvent $event) => !$existsBookmakerEvents
                ->contains(function (BookmakerEvent $existEvent) use ($event) {
                    return $this->createBookmakerEventUniqueKey($event) == $this->createBookmakerEventUniqueKey($existEvent);
                })
        )->values();

        $events = $events->map(function (EventSyncDTO $dto) use ($bookmakerTournaments) {
            $bookmakerTournament = $bookmakerTournaments
                ->where('external_id', $dto->tournament_id)
                ->firstWhere('bookmaker', Bookmaker::fromValue($dto->bookmaker));

            if ($bookmakerTournament == null) {
                return null;
            }

            return new Event([
                'tournament_id' => $bookmakerTournament->tournament_id,
            ]);
        })->filter();

        $events = $events->map(
            fn(Event $event) => $this->fillEvent($event)
        );

        $filledBookmakerEvents = collect();
        for ($index = 0; $index < $bookmakerEvents->count(); $index++) {
            $event = $events->get($index);
            $bookmakerEvent = $bookmakerEvents->get($index);

            $bookmakerEvent->event_id = $event->id;

            $filledBookmakerEvents->push($bookmakerEvent);
        }

        $bookmakerEvents = $filledBookmakerEvents->map(
            fn(BookmakerEvent $bookmakerEvent) => $this->fillBookmakerEvent($bookmakerEvent)
        );


        Event::query()->insert($events->toArray());
        BookmakerEvent::query()->insert($bookmakerEvents->toArray());
    }

    public function deleteExceptMany(Collection $events): void
    {
        $bookmakerEvents = $events->map(
            fn(EventSyncDTO $dto) => $this->createBookmakerEventFromDTO($dto)
        );

        $bookmakerEvents = $this->receiveBookmakerEvents($bookmakerEvents);

        $this->deleteExceptBookmakerEvents($bookmakerEvents);
        $this->deleteExceptEventsFromBookmakerEvents($bookmakerEvents);
    }

    public function sync(SyncSourceContract $source): void
    {
        $events = $source->events();

        $this->createOrUpdateMany($events);
        $this->wrappee->sync($source);
        $this->deleteExceptMany($events);
    }
}
