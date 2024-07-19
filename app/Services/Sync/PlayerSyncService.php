<?php

namespace App\Services\Sync;

use App\Contracts\SyncServiceContract;
use App\Contracts\SyncSourceContract;
use App\DTOs\PlayerSyncDTO;
use App\Models\BookmakerPlayer;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayerSyncService extends SyncServiceDecorator
{
    private function createBookmakerPlayerFromDTO(PlayerSyncDTO $dto): BookmakerPlayer
    {
        return new BookmakerPlayer([
            'name' => $dto->name,
            'is_short_name' => $dto->is_short_name,
            'bookmaker' => $dto->bookmaker,
        ]);
    }

    private function createPlayerFromBookmakerPlayer(BookmakerPlayer $player): Player
    {
        return new Player();
    }

    private function createUniqueKey(BookmakerPlayer $player): string
    {
        return "$player->name|$player->is_short_name|$player->bookmaker";
    }

    private function fillPlayer(Player $player): Player
    {
        $player->id = Str::uuid();

        $player->created_at = now();
        $player->updated_at = now();

        return $player;
    }

    private function fillBookmakerPlayer(BookmakerPlayer $player): BookmakerPlayer
    {
        $player->id = Str::uuid();

        $player->created_at = now();
        $player->updated_at = now();

        return $player;
    }

    public function createOrUpdateMany(Collection $players): void
    {
        $bookmakerPlayers = $players->map(
            fn(PlayerSyncDTO $dto) => $this->createBookmakerPlayerFromDTO($dto)
        );

        $bookmakerPlayerUniqueKeys = $bookmakerPlayers->map(
            fn(BookmakerPlayer $bookmakerPlayer) => $this->createUniqueKey($bookmakerPlayer)
        );

        $existsBookmakerPlayers = BookmakerPlayer::query()
            ->whereIn(DB::raw("concat(name, '|', is_short_name, '|', 'bookmaker')"), $bookmakerPlayerUniqueKeys)
            ->get();

        $bookmakerPlayers = $bookmakerPlayers->filter(fn(BookmakerPlayer $player) => !$existsBookmakerPlayers
            ->contains(function (BookmakerPlayer $existsPlayer) use ($player) {
                return $this->createUniqueKey($player) != $this->createUniqueKey($existsPlayer);
            })
        );

        $players = $bookmakerPlayers->map(
            fn(BookmakerPlayer $player) => $this->createPlayerFromBookmakerPlayer($player)
        );

        $players = $players->map(fn(Player $player) => $this->fillPlayer($player));

        $filledBookmakerPlayers = collect();
        for ($index = 0; $index < $bookmakerPlayers->count(); $index++) {
            $player = $players->get($index);
            $bookmakerPlayer = $bookmakerPlayers->get($index);

            $bookmakerPlayer->player_id = $player->id;

            $filledBookmakerPlayers->push($bookmakerPlayer);
        }

        $bookmakerTournaments = $filledBookmakerPlayers->map(
            fn(BookmakerPlayer $bookmakerPlayer) => $this->fillBookmakerPlayer($bookmakerPlayer)
        );

        Player::query()->insert($players->toArray());
        BookmakerPlayer::query()->insert($bookmakerTournaments->toArray());
    }

    public function deleteExceptMany(Collection $players): void
    {
        return;
    }

    public function sync(SyncSourceContract $source): void
    {
        $players = $source->players();

        $this->createOrUpdateMany($players);
        $this->wrappee->sync($source);
        $this->deleteExceptMany($players);
    }
}
