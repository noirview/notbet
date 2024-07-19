<?php

namespace App\Services\Sync;

use App\Contracts\SyncServiceContract;
use App\Contracts\SyncSourceContract;
use App\DTOs\BetSyncDTO;
use App\Enums\Bookmaker;
use App\Models\Bet;
use App\Models\BookmakerEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BetSyncService implements SyncServiceContract
{
    private Collection $create;
    private Collection $update;

    private function createBetUniqueKey(Bet $bet): string
    {
        return "$bet->event_id|$bet->type|$bet->number_team|$bet->number_period|$bet->sign|$bet->value|$bet->coefficient|$bet->bookmaker";
    }

    private function createBookmakerEventUniqueKey(BookmakerEvent $event): string
    {
        return "$event->external_id|$event->bookmaker";
    }

    private function partition(Collection $bets): void
    {
        $bookmakerEvents = $bets->map(
            fn(BetSyncDTO $dto) => new BookmakerEvent([
                'external_id' => $dto->eventExternalId,
                'bookmaker' => $dto->bookmaker,
            ])
        );

        $bookmakerEventUniqueKeys = $bookmakerEvents->map(
            fn(BookmakerEvent $bookmakerEvent) => $this->createBookmakerEventUniqueKey($bookmakerEvent)
        );

        $bookmakerEvents = BookmakerEvent::query()
            ->whereIn(DB::raw("concat(external_id, '|', bookmaker)"), $bookmakerEventUniqueKeys)
            ->get();

        $bets = $bets->map(function (BetSyncDTO $dto) use ($bookmakerEvents) {
            $bookmakerEvent = $bookmakerEvents
                ->where('external_id', $dto->eventExternalId)
                ->firstWhere('bookmaker', Bookmaker::fromValue($dto->bookmaker));

            return new Bet([
                'event_id' => $bookmakerEvent->event_id,
                'type' => $dto->type,
                'number_team' => $dto->numberTeam,
                'number_period' => $dto->numberPeriod,
                'sign' => $dto->sign,
                'value' => $dto->value,
                'coefficient' => $dto->coefficient,
                'bookmaker' => $dto->bookmaker,
            ]);
        });

        $betUniqueKeys = $bets->map(
            fn(Bet $bet) => $this->createBetUniqueKey($bet)
        );

        $existBets = Bet::query()
            ->whereIn(DB::raw("concat(event_id, '|', type, '|', number_team, '|', number_period, '|', sign, '|', value, '|', coefficient, '|', bookmaker)"), $betUniqueKeys)
            ->get();

        [$this->update, $this->create] = $bets->partition(
            fn(Bet $bet) => $existBets->contains(
                fn(Bet $existsBet) => $this->createBetUniqueKey($bet) == $this->createBetUniqueKey($existsBet)
            )
        );
    }

    private function createMany(): void
    {
        $bets = $this->create->map(function (Bet $bet) {
            $bet->id = Str::uuid()->toString();

            $bet->updated_at = now();
            $bet->created_at = now();

            return $bet;
        });

        Bet::query()->insert($bets->toArray());

        $this->create = $bets;
    }

    private function updateMany(): void
    {
        Bet::query()
            ->upsert($this->update->toArray(),
                ['event_id', 'type', 'number_team', 'number_period', 'sign', 'value', 'bookmaker'],
                ['coefficient']
            );
    }

    private function deleteMany()
    {
        $bets = collect()
            ->merge($this->create)
            ->merge($this->update);

        Bet::query()
            ->whereNotIn('id', $bets->pluck('id'))
            ->delete();
    }

    public function sync(SyncSourceContract $source): void
    {
        $bets = $source->bets();

        $this->partition($bets);

        $this->createMany();
        $this->updateMany();
        $this->deleteMany();
    }
}
