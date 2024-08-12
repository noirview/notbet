<?php

namespace App\Services\Bookmakers\Maxline;

use App\Contracts\SyncSourceContract;
use App\DTOs\BetSyncDTO;
use App\DTOs\EventPlayerSyncDTO;
use App\DTOs\EventSyncDTO;
use App\DTOs\PlayerSyncDTO;
use App\DTOs\TournamentSyncDTO;
use App\Enums\Bet\NumberTeam;
use App\Enums\Bet\Sign;
use App\Enums\Bet\Type;
use App\Enums\Bookmaker;
use App\Enums\EventPlayer\TeamNumber;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MaxlineService implements SyncSourceContract
{

    private Client $client;
    private Collection $tournaments;
    private Collection $events;
    private Collection $eventPlayers;
    private Collection $players;
    private Collection $bets;

    public function __construct()
    {
        $this->client = Http::buildClient();
        Http::setClient($this->client)->get('https://maxline.by/');
        Http::setClient($this->client)
            ->asForm()
            ->post('https://maxline.by/api/languages/set-lang', [
                'lang' => 'en',
            ]);

        $this->tournaments = collect();
        $this->events = collect();
        $this->players = collect();
        $this->eventPlayers = collect();
        $this->bets = collect();
    }

    public function parse()
    {
        $response = Http::setClient($this->client)
            ->get('https://maxline.by/api/league/line-sport', [
                'sport' => 3,
                'period' => 0
            ]);

        $leaguesJson = $response->json('data.leagues.leagues');
        $this->tournaments = collect($leaguesJson)->map(function (array $leagueJson) {
            return new TournamentSyncDTO(
                Arr::get($leagueJson, 'id'),
                Arr::get($leagueJson, 'name'),
                Bookmaker::MAXLINE,
            );
        });

        $response = Http::setClient($this->client)
            ->get('https://maxline.by/api/event/line-data', [
                'league' => $this->tournaments->pluck('id')->join('-'),
                'express_plus' => 0,
                'period' => 0,
                'country_code' => ''
            ]);

        $eventsJson = $response->json('data.events');
        collect($eventsJson)->each(function (array $eventJson) {
            $event = new EventSyncDTO(
                Arr::get($eventJson, 'id'),
                Arr::get($eventJson, 'league_id'),
                Carbon::make(Arr::get($eventJson, 'time')),
                Bookmaker::MAXLINE,
            );

            collect([
                Arr::get($eventJson, 'team1'),
                Arr::get($eventJson, 'team2'),
            ])->each(function (string $team, int $teamNumber) use ($event) {
                $teamNumber = match ($teamNumber) {
                    0 => TeamNumber::FIRST,
                    1 => TeamNumber::SECOND,
                    default => null,
                };

                $isDouble = Str::of($team)->contains('/');

                Str::of($team)->explode('/')
                    ->each(function (string $playerName, int $positionNumber) use ($event, $isDouble, $teamNumber) {
                        $player = new PlayerSyncDTO(
                            $playerName,
                            $isDouble,
                            Bookmaker::MAXLINE,
                        );

                        $eventPlayer = new EventPlayerSyncDTO(
                            $event->id,
                            $playerName,
                            $isDouble,
                            $teamNumber,
                            $positionNumber,
                            Bookmaker::MAXLINE,
                        );

                        $this->players->push($player);
                        $this->eventPlayers->push($eventPlayer);
                    });
            });

            $this->events->push($event);
        });

        $betsJson = $response->json('data.factors');
        collect($betsJson)->reduce(function (Collection $bets, array $eventBets) {
            return $bets->merge($eventBets);
        }, collect())->each(function (array $betJson) {
            $bet = match ($this->getType($betJson)) {
                Type::WINNER => $this->winnerBet($betJson),
                Type::HANDICAP => $this->handicapBet($betJson),
                Type::TOTAL => $this->totalBet($betJson),
                default => null,
            };

            if ($bet) {
                $this->bets->push($bet);
            }
        });
    }

    public function tournaments(): Collection
    {
        return $this->tournaments
            ->filter(fn(TournamentSyncDTO $tournament) => !Str::of($tournament->name)->contains([
                'aces',
                'double faults',
                'Name The Finalists',
                'Winner',
            ]))
            ->unique(fn(TournamentSyncDTO $tournament) => "$tournament->id|$tournament->bookmaker");
    }


    public function events(): Collection
    {
        return $this->events
            ->unique(fn(EventSyncDTO $event) => "$event->id");
    }

    public function players(): Collection
    {
        return $this->players
            ->unique(fn(PlayerSyncDTO $player) => "$player->name|$player->is_short_name");
    }

    public function eventPlayers(): Collection
    {
        return $this->eventPlayers
            ->unique(fn(EventPlayerSyncDTO $eventPlayer) => "$eventPlayer->eventId|$eventPlayer->playerName|$eventPlayer->isShortPlayerName");
    }

    public function bets(): Collection
    {
        return $this->bets
            ->unique(fn(BetSyncDTO $bet) => "$bet->eventExternalId|$bet->type|$bet->numberTeam|$bet->numberPeriod|$bet->sign|$bet->value");
    }

    private function getType(array $betJson): int|null
    {
        $type = Arr::get($betJson, 't');
        $period = Arr::get($betJson, 'p');

        if ($period != 0) return false;

        return match ($type) {
            1 => Type::WINNER,
            3 => Type::HANDICAP,
            4 => Type::TOTAL,
            default => null,
        };
    }

    private function winnerBet(array $betJson): BetSyncDTO
    {
        $numberTeam = match (Arr::get($betJson, 'i')) {
            1 => NumberTeam::FIRST,
            2 => NumberTeam::SECOND,
            default => null,
        };

        return new BetSyncDTO(
            Arr::get($betJson, 'e'),
            Type::WINNER,
            $numberTeam,
            null,
            null,
            null,
            floatval(Arr::get($betJson, 'v')),
            Bookmaker::MAXLINE,
        );
    }

    private function handicapBet(array $betJson): BetSyncDTO
    {
        $numberTeam = match (Arr::get($betJson, 'i')) {
            1 => NumberTeam::FIRST,
            2 => NumberTeam::SECOND,
            default => null,
        };

        $value = floatval(Arr::get($betJson, 'pt'));
        $sign = match (true) {
            $value > 0 => Sign::PLUS,
            $value < 0 => Sign::MINUS,
            default => null,
        };

        return new BetSyncDTO(
            Arr::get($betJson, 'e'),
            Type::TOTAL,
            $numberTeam,
            null,
            $sign,
            abs($value),
            floatval(Arr::get($betJson, 'v')),
            Bookmaker::MAXLINE,
        );
    }

    private function totalBet(array $betJson): BetSyncDTO
    {
        $sign = match (Arr::get($betJson, 'i')) {
            1 => Sign::UNDER,
            2 => Sign::OVER,
            default => null,
        };

        return new BetSyncDTO(
            Arr::get($betJson, 'e'),
            Type::TOTAL,
            null,
            null,
            $sign,
            floatval(Arr::get($betJson, 'pt')),
            floatval(Arr::get($betJson, 'v')),
            Bookmaker::MAXLINE,
        );
    }
}
