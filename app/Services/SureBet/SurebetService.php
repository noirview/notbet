<?php

namespace App\Services\SureBet;

use App\Enums\Bet\NumberTeam;
use App\Enums\Bet\Sign;
use App\Enums\Bet\Type;
use App\Models\Bet;
use App\Models\Event;
use App\Models\SurebetBet;
use Illuminate\Support\Collection;

class SurebetService
{
    public function find(Event $event): void
    {
        $bets = $event->bets;

        $winnerBets = $bets->where('type', Type::fromValue(Type::WINNER));
        $handicapBets = $bets->where('type', Type::fromValue(Type::HANDICAP));
        $totalBets = $bets->where('type', Type::fromValue(Type::TOTAL));

        $this->fromWinnerBets($winnerBets);
        $this->fromHandicapBets($handicapBets);
        $this->fromTotalBets($totalBets);
    }

    private function fromWinnerBets(Collection $winnerBets): void
    {
        $firstTeamWinnerBets = $winnerBets->where('number_team',
            NumberTeam::fromValue(NumberTeam::FIRST)
        )->sortByDesc('coefficient');
        $secondTeamWinnerBets = $winnerBets->where('number_team',
            NumberTeam::fromValue(NumberTeam::SECOND)
        )->sortByDesc('coefficient');

        $firstTeamWinnerBetsIterator = $firstTeamWinnerBets->getIterator();
        $secondTeamWinnerBetsIterator = $secondTeamWinnerBets->getIterator();

        $this->findSurebets($firstTeamWinnerBetsIterator, $secondTeamWinnerBetsIterator);
    }

    private function fromHandicapBets(Collection $handicapBets): void
    {
        $values = $handicapBets->unique('value')->pluck('value');

        $values->each(function (float|null $value) use ($handicapBets) {
            if (!$value) {
                return;
            }

            $valueHandicapBets = $handicapBets->where('value', $value);

            $firstTeamHandicapBets = $valueHandicapBets->where('number_team',
                NumberTeam::fromValue(NumberTeam::FIRST)
            );
            $secondTeamHandicapBets = $valueHandicapBets->where('number_team',
                NumberTeam::fromValue(NumberTeam::SECOND)
            );

            if ($value == 0) {
                $this->findSurebets(
                    $firstTeamHandicapBets->getIterator(),
                    $secondTeamHandicapBets->getIterator(),
                );
            }

            $plusFirstTeamHandicapBets = $firstTeamHandicapBets->where('sign',
                Sign::fromValue(Sign::PLUS)
            )->sortByDesc('coefficient');
            $minusSecondTeamHandicapBets = $secondTeamHandicapBets->where('sign',
                Sign::fromValue(Sign::MINUS)
            )->sortByDesc('coefficient');

            $this->findSurebets(
                $plusFirstTeamHandicapBets->getIterator(),
                $minusSecondTeamHandicapBets->getIterator(),
            );

            $minusFirstTeamHandicapBets = $firstTeamHandicapBets->where('extra',
                Sign::fromValue(Sign::MINUS)
            )->sortByDesc('coefficient');
            $plusSecondTeamHandicapBets = $secondTeamHandicapBets->where('extra',
                Sign::fromValue(Sign::PLUS)
            )->sortByDesc('coefficient');

            $this->findSurebets(
                $minusFirstTeamHandicapBets->getIterator(),
                $plusSecondTeamHandicapBets->getIterator(),
            );
        });
    }

    private function fromTotalBets(Collection $totalBets): void
    {
        $values = $totalBets->unique('value')->pluck('value');

        $values->each(function (float $value) use ($totalBets) {
            $valueTotalBets = $totalBets->where('value', $value);

            $overTotalBets = $valueTotalBets->where('sign', Sign::fromValue(Sign::OVER))
                ->sortByDesc('coefficient');
            $underTotalBets = $valueTotalBets->where('sign', Sign::fromValue(Sign::UNDER))
                ->sortByDesc('coefficient');

            $this->findSurebets(
                $overTotalBets->getIterator(),
                $underTotalBets->getIterator(),
            );
        });
    }

    private function findSurebets(\Traversable $firstIterator, \Traversable $secondIterator): void
    {
        while (true) {
            $firstBet = $firstIterator->current();
            $secondBet = $secondIterator->current();

            if (is_null($firstBet) || is_null($secondBet)) {
                break;
            }

            $firstIterator->next();
            $secondIterator->next();

            if ($firstBet->bookmaker == $secondBet->bookmaker) {
                continue;
            }

            $firstTeamWinnerCoefficient = $firstBet->coefficient;
            $secondTeamWinnerCoefficient = $secondBet->coefficient;

            $surebet = 1 / $firstTeamWinnerCoefficient + 1 / $secondTeamWinnerCoefficient;

            if ($surebet < config('surebet.max_value')) {
                $this->createFork($firstBet, $secondBet);
            }
        }

    }

    private function createFork(Bet $firstBet, Bet $secondBet): void
    {
        $surebetBets = SurebetBet::query()
            ->whereIn('bet_id', [
                $firstBet->id,
                $secondBet->id,
            ])->get();

        $firstBetSurebets = $surebetBets->where('bet_id', $firstBet->id)
            ->pluck('surebet_id');

        $secondBetSurebets = $surebetBets->where('bet_id', $secondBet->id)
            ->pluck('surebet_id');

        $forkIds = collect()
            ->merge($firstBetSurebets)
            ->merge($secondBetSurebets)
            ->duplicates()
            ->values();

        $hasSurebet = false;

        $forkIds->each(function (string $surebetId) use ($firstBet, $secondBet, &$hasSurebet) {
            $firstForkIds = $firstBet->forks->pluck('id');
            $secondForkIds = $secondBet->forks->pluck('id');

            if (in_array($surebetId, $firstForkIds->toArray()) && in_array($surebetId, $secondForkIds->toArray())) {
                $hasSurebet = true;
            }
        });

        if (!$hasSurebet) {
            $surebet = SurebetBet::create();

            $surebet->bets()->attach([
                $firstBet->id,
                $secondBet->id,
            ]);
        }
    }
}
