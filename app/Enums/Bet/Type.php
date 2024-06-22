<?php declare(strict_types=1);

namespace App\Enums\Bet;

use BenSampo\Enum\Enum;

final class Type extends Enum
{
    const int WINNER = 0;
    const int HANDICAP = 1;
    const int TOTAL = 2;
}
