<?php

namespace App\Services\Sync;

use App\Contracts\SyncSourceContract;
use App\DTOs\TournamentSyncDTO;
use App\Models\BookmakerTournament;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TournamentSyncService extends SyncServiceDecorator
{
    private function createBookmakerTournamentFromDTO(TournamentSyncDTO $dto): BookmakerTournament
    {
        return new BookmakerTournament([
            'external_id' => $dto->id,
            'name' => $dto->name,
            'bookmaker' => $dto->bookmaker,
        ]);
    }

    private function createTournamentFromBookmakerTournament(BookmakerTournament $bookmakerTournament): Tournament
    {
        return new Tournament();
    }

    private function createUniqueKey(BookmakerTournament $tournament): string
    {
        return "$tournament->external_id|$tournament->bookmaker";
    }

    private function receiveExistsBookmakerTournamentsByUniqueKey(Collection $uniqueKeys): Collection
    {
        return BookmakerTournament::query()
            ->whereIn(DB::raw("concat(external_id, '|', bookmaker)"), $uniqueKeys)
            ->get();
    }

    private function fillTournament(Tournament $tournament): Tournament
    {
        $tournament->id = Str::uuid()->toString();

        $tournament->created_at = now();
        $tournament->updated_at = now();

        return $tournament;
    }

    private function fillBookmakerTournament(BookmakerTournament $tournament): BookmakerTournament
    {
        $tournament->id = Str::uuid()->toString();

        $tournament->created_at = now();
        $tournament->updated_at = now();

        return $tournament;
    }

    private function receiveExistsBookmakerTournaments(Collection $bookmakerTournaments): Collection
    {
        $bookmakerTournamentUniqueKeys = $bookmakerTournaments->map(
            fn(BookmakerTournament $tournament) => $this->createUniqueKey($tournament)
        );

        return $this->receiveExistsBookmakerTournamentsByUniqueKey($bookmakerTournamentUniqueKeys);
    }

    private function receiveBookmakerTournaments(Collection $tournamentDTOs): Collection
    {
        $bookmakerTournaments = $tournamentDTOs->map(
            fn(TournamentSyncDTO $dto) => $this->createBookmakerTournamentFromDTO($dto)
        );

        $existsBookmakerTournaments = $this->receiveExistsBookmakerTournaments($bookmakerTournaments);

        $bookmakerTournaments = $bookmakerTournaments->filter(
            fn(BookmakerTournament $tournament) => !$existsBookmakerTournaments
                ->contains(function (BookmakerTournament $existTournament) use ($tournament) {
                    return $this->createUniqueKey($tournament) == $this->createUniqueKey($existTournament);
                })
        )->values();

        return $bookmakerTournaments;
    }

    private function storeTournaments(Collection $tournaments): Collection
    {
        $tournaments = $tournaments->map(
            fn(Tournament $tournament) => $this->fillTournament($tournament)
        );

        Tournament::query()->insert($tournaments->toArray());

        return $tournaments;
    }

    private function storeBookmakerTournaments(Collection $bookmakerTournaments): Collection
    {
        $bookmakerTournaments = $bookmakerTournaments->map(
            fn(BookmakerTournament $bookmakerTournament) => $this->fillBookmakerTournament($bookmakerTournament)
        );

        BookmakerTournament::query()->insert($bookmakerTournaments->toArray());

        return $bookmakerTournaments;
    }

    private function deleteExceptBookmakerTournaments(Collection $bookmakerTournaments): void
    {
        $bookmakerTournamentIds = $bookmakerTournaments->pluck('id');

        BookmakerTournament::query()
            ->whereNotIn('id', $bookmakerTournamentIds)
            ->delete();
    }

    private function deleteExceptTournamentsFromBookmakerTournaments(Collection $bookmakerTournaments): void
    {
        $tournamentIds = $bookmakerTournaments->pluck('tournament_id');

        Tournament::query()
            ->doesntHave('bookmakerTournaments')
            ->whereNotIn('id', $tournamentIds)
            ->delete();
    }

    private function createOrUpdateMany($tournamentDTOs): void
    {
        $bookmakerTournaments = $this->receiveBookmakerTournaments($tournamentDTOs);

        $tournaments = $bookmakerTournaments->map(
            fn(BookmakerTournament $bookmakerTournament) => $this->createTournamentFromBookmakerTournament($bookmakerTournament)
        );

        $tournaments = $this->storeTournaments($tournaments);

        $filledBookmakerTournaments = collect();
        for ($index = 0; $index < $bookmakerTournaments->count(); $index++) {
            $tournament = $tournaments->get($index);
            $bookmakerTournament = $bookmakerTournaments->get($index);

            $bookmakerTournament->tournament_id = $tournament->id;

            $filledBookmakerTournaments->push($bookmakerTournament);
        }

        $this->storeBookmakerTournaments($filledBookmakerTournaments);
    }

    private function deleteExceptMany(Collection $tournaments): void
    {
        $bookmakerTournaments = $tournaments->map(
            fn(TournamentSyncDTO $dto) => $this->createBookmakerTournamentFromDTO($dto)
        );

        $bookmakerTournaments = $this->receiveExistsBookmakerTournaments($bookmakerTournaments);

        $this->deleteExceptBookmakerTournaments($bookmakerTournaments);
        $this->deleteExceptTournamentsFromBookmakerTournaments($bookmakerTournaments);
    }

    public function sync(SyncSourceContract $source): void
    {
        $tournaments = $source->tournaments();

        $this->createOrUpdateMany($tournaments);
        $this->wrappee->sync($source);
        $this->deleteExceptMany($tournaments);
    }
}
