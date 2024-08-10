<?php

namespace App\Console\Commands\Events;

use App\Enums\Bookmaker;
use App\Enums\EventPlayer\PositionNumber;
use App\Enums\EventPlayer\TeamNumber;
use App\Models\BookmakerEvent;
use App\Models\BookmakerTournament;
use App\Models\Event;
use App\Models\Tournament;
use App\Services\SpreadSheet\SpreadSheetService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class _Print extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:print';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(SpreadSheetService $service)
    {
        $tournaments = Tournament::query()
            ->select('id')
            ->selectSub(
                BookmakerTournament::query()->select('name')
                    ->whereColumn('bookmaker_tournaments.tournament_id', 'tournaments.id')
                    ->orderBy('bookmaker')
                    ->limit(1),
                'name'
            )
            ->has('bookmakerTournaments', '>', 1)
            ->get();

        $tournamentName = $this->choice('Select tournament', $tournaments->pluck('name')->toArray());

        $tournament = $tournaments->firstWhere('name', $tournamentName);

        $events = Event::query()->select(['id', 'tournament_id'])
            ->with(['players', 'bookmakerEvents'])
            ->where('tournament_id', $tournament->id)
            ->get()
            ->map(function (Event $event) {
                if ($event->players->count() > 2) {
                    $firstTeamFirstPlayer = $event->players
                        ->where('pivot.team_number', TeamNumber::fromValue(TeamNumber::FIRST))
                        ->firstWhere('pivot.position_number', PositionNumber::fromValue(PositionNumber::FIRST))
                        ->bookmakerPlayers->first()->name;

                    $firstTeamSecondPlayer = $event->players
                        ->where('pivot.team_number', TeamNumber::fromValue(TeamNumber::FIRST))
                        ->firstWhere('pivot.position_number', PositionNumber::fromValue(PositionNumber::SECOND))
                        ->bookmakerPlayers->first()->name;

                    $secondTeamFirstPlayer = $event->players
                        ->where('pivot.team_number', TeamNumber::fromValue(TeamNumber::SECOND))
                        ->firstWhere('pivot.position_number', PositionNumber::fromValue(PositionNumber::FIRST))
                        ->bookmakerPlayers->first()->name;

                    $secondTeamSecondPlayer = $event->players
                        ->where('pivot.team_number', TeamNumber::fromValue(TeamNumber::SECOND))
                        ->firstWhere('pivot.position_number', PositionNumber::fromValue(PositionNumber::SECOND))
                        ->bookmakerPlayers->first()->name;

                    $event->name = "$firstTeamFirstPlayer / $firstTeamSecondPlayer - $secondTeamFirstPlayer / $secondTeamSecondPlayer";
                    return $event;

                } else {
                    $firstTeamFirstPlayer = $event->players
                        ->where('pivot.team_number', TeamNumber::fromValue(TeamNumber::FIRST))
                        ->firstWhere('pivot.position_number', PositionNumber::fromValue(PositionNumber::FIRST))
                        ->bookmakerPlayers->first()->name;

                    $secondTeamFirstPlayer = $event->players
                        ->where('pivot.team_number', TeamNumber::fromValue(TeamNumber::SECOND))
                        ->firstWhere('pivot.position_number', PositionNumber::fromValue(PositionNumber::FIRST))
                        ->bookmakerPlayers->first()->name;

                    $event->name = "$firstTeamFirstPlayer - $secondTeamFirstPlayer";
                    return $event;
                }
            })->reduce(function (Collection $events, Event $event) {
                $bookmakerEvents = $event->bookmakerEvents
                    ->map(function (BookmakerEvent $bookmakerEvent) use ($event) {
                        $bookmakerEvent->name = $event->name;
                        return $bookmakerEvent;
                    });

                $events = $events->merge($bookmakerEvents);
                return $events;
            }, collect());

        $service->print(
            $events,
            'event_id'
        );
    }
}
