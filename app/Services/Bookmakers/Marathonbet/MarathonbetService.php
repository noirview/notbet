<?php

namespace App\Services\Bookmakers\Marathonbet;

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
use App\Enums\EventPlayer\PositionNumber;
use App\Enums\EventPlayer\TeamNumber;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class MarathonbetService implements SyncSourceContract
{
    private Collection $tournaments;
    private Collection $events;
    private Collection $players;
    private Collection $eventPlayers;
    private Collection $bets;

    public function __construct()
    {
        $this->tournaments = collect();
        $this->events = collect();
        $this->players = collect();
        $this->eventPlayers = collect();
        $this->bets = collect();
    }

    public function parse()
    {
        $html = '';

        $page = 1;
        do {
            $response = Http::get('https://www.marathonbet.by/en/betting/Tennis+-+2398', [
                'page' => $page,
                'pageAction' => 'getPage',
                '_' => now()->getTimestampMs(),
            ]);

            $jsonResponse = $response->json();

            $html .= Arr::get($jsonResponse, '0.content');

            $hasNextPage = Arr::get($jsonResponse, '1.val');

            $page++;
        } while ($hasNextPage);

        $parser = new Crawler($html);
        $parser->filter('.category-container')
            ->each(function (Crawler $tournamentNode) {
                $tournament = new TournamentSyncDTO(
                    $tournamentNode->attr('data-category-treeid'),
                    $tournamentNode->filter('.category-label')->text(),
                    Bookmaker::MARATHONBET,
                );

                $tournamentNode->filter('.bg.coupon-row')
                    ->each(function (Crawler $eventNode) use ($tournament) {

                        $startAtString = $eventNode->filter('.score-and-time')->text();
                        $startAt = match (true) {
                            Carbon::hasFormat($startAtString, 'H:i') => Carbon::createFromFormat('H:i', $startAtString, 'Europe/Minsk'),
                            Carbon::hasFormat($startAtString, 'd M H:i') => Carbon::createFromFormat('d M H:i', $startAtString, 'Europe/Minsk'),
                            default => null,
                        };

                        $event = new EventSyncDTO(
                            $eventNode->attr('data-event-eventid'),
                            $tournament->id,
                            $startAt,
                            Bookmaker::MARATHONBET,
                        );

                        $eventName = $eventNode->filter('.member-names-view')->text();
                        Str::of($eventName)->explode(' — ')
                            ->each(function (string $teamName, int $teamNumber) use ($event) {
                                $isDouble = Str::of($teamName)->contains('/');
                                Str::of($teamName)->explode(' / ')
                                    ->each(function (string $playerName, int $positionNumber) use ($teamNumber, $event, $isDouble) {
                                        $player = new PlayerSyncDTO(
                                            $playerName,
                                            $isDouble,
                                            Bookmaker::MARATHONBET,
                                        );

                                        $eventPlayer = new EventPlayerSyncDTO(
                                            $event->id,
                                            $playerName,
                                            $isDouble,
                                            $teamNumber == 0 ? TeamNumber::FIRST : TeamNumber::SECOND,
                                            $positionNumber == 0 ? PositionNumber::FIRST : PositionNumber::SECOND,
                                            Bookmaker::MARATHONBET,
                                        );

                                        $this->players->push($player);
                                        $this->eventPlayers->push($eventPlayer);
                                    });
                            });

                        $firstWinnerBetNode = $eventNode->filter('[data-mutable-id="S_0_1_european"]');
                        $firstWinnerCoefficientNode = $firstWinnerBetNode->filter('.selection-link.active-selection');

                        $secondWinnerBetNode = $eventNode->filter('[data-mutable-id="S_0_3_european"]');
                        $secondWinnerCoefficientNode = $secondWinnerBetNode->filter('.selection-link.active-selection');

                        $firstHandicapBetNode = $eventNode->filter('[data-mutable-id="S_1_1_european"]');
                        $hasFirstHandicapBet = $firstHandicapBetNode->count() !== 0;
                        $firstHandicapValueNode = $firstHandicapBetNode->filter('.middle-simple');
                        $firstHandicapCoefficientNode = $firstHandicapBetNode->filter('.selection-link.active-selection');

                        $secondHandicapBetNode = $eventNode->filter('[data-mutable-id="S_1_3_european"]');
                        $hasSecondHandicapBet = $secondHandicapBetNode->count() !== 0;
                        $secondHandicapValueNode = $secondHandicapBetNode->filter('.middle-simple');
                        $secondHandicapCoefficientNode = $secondHandicapBetNode->filter('.selection-link.active-selection');

                        $firstTotalBetNode = $eventNode->filter('[data-mutable-id="S_2_1_european"]');
                        $hasFirstTotalBet = $firstTotalBetNode->count() !== 0;
                        $firstTotalValueNode = $firstTotalBetNode->filter('.middle-simple');
                        $firstTotalCoefficientNode = $firstTotalBetNode->filter('.selection-link.active-selection');

                        $secondTotalBetNode = $eventNode->filter('[data-mutable-id="S_2_3_european"]');
                        $hasSecondTotalBet = $secondTotalBetNode->count() !== 0;
                        $secondTotalValueNode = $secondTotalBetNode->filter('.middle-simple');
                        $secondTotalCoefficientNode = $secondTotalBetNode->filter('.selection-link.active-selection');

                        $firstWinnerBet = new BetSyncDTO(
                            $event->id,
                            Type::WINNER,
                            NumberTeam::FIRST,
                            null,
                            null,
                            null,
                            floatval($firstWinnerCoefficientNode->attr('data-selection-price')),
                            Bookmaker::MARATHONBET,
                        );

                        $secondWinnerBet = new BetSyncDTO(
                            $event->id,
                            Type::WINNER,
                            NumberTeam::SECOND,
                            null,
                            null,
                            null,
                            floatval($secondWinnerCoefficientNode->attr('data-selection-price')),
                            Bookmaker::MARATHONBET,
                        );

                        if ($hasFirstHandicapBet) {
                            $firstHandicapValue = Str::of($firstHandicapValueNode->text())->between('(', ')')->toFloat();
                            $firstHandicapBet = new BetSyncDTO(
                                $event->id,
                                Type::HANDICAP,
                                NumberTeam::FIRST,
                                null,
                                $firstHandicapValue > 0 ? Sign::PLUS : Sign::MINUS,
                                abs($firstHandicapValue),
                                floatval($firstHandicapCoefficientNode->text()),
                                Bookmaker::MARATHONBET,
                            );
                        }

                        if ($hasSecondHandicapBet) {
                            $secondHandicapValue = Str::of($secondHandicapValueNode->text())->between('(', ')')->toFloat();
                            $secondHandicapBet = new BetSyncDTO(
                                $event->id,
                                Type::HANDICAP,
                                NumberTeam::SECOND,
                                null,
                                $secondHandicapValue > 0 ? Sign::PLUS : Sign::MINUS,
                                abs($secondHandicapValue),
                                floatval($secondHandicapCoefficientNode->text()),
                                Bookmaker::MARATHONBET,
                            );
                        }

                        if ($hasFirstTotalBet) {
                            $firstTotalBet = new BetSyncDTO(
                                $event->id,
                                Type::TOTAL,
                                null,
                                null,
                                Sign::UNDER,
                                floatval($firstTotalValueNode->text()),
                                floatval($firstTotalCoefficientNode->text()),
                                Bookmaker::MARATHONBET,
                            );
                        }

                        if ($hasSecondTotalBet) {
                            $secondTotalBet = new BetSyncDTO(
                                $event->id,
                                Type::TOTAL,
                                null,
                                null,
                                Sign::OVER,
                                floatval($secondTotalValueNode->text()),
                                floatval($secondTotalCoefficientNode->text()),
                                Bookmaker::MARATHONBET,
                            );
                        }

                        $this->bets->push(
                            $firstWinnerBet, $secondWinnerBet,
                        );

                        if ($hasFirstHandicapBet) {
                            $this->bets->push($firstHandicapBet);
                        }

                        if ($hasSecondHandicapBet) {
                            $this->bets->push($secondHandicapBet);
                        }

                        if ($hasFirstTotalBet) {
                            $this->bets->push($firstTotalBet);
                        }

                        if ($hasSecondTotalBet) {
                            $this->bets->push($secondTotalBet);
                        }

                        $this->events->push($event);
                    });

                $this->tournaments->push($tournament);
            });
    }

    public function tournaments(): Collection
    {
        return $this->tournaments
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
}
