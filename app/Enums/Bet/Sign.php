<?php declare(strict_types=1);

namespace App\Enums\Bet;

use BenSampo\Enum\Enum;

final class Sign extends Enum
{
    const int PLUS = 0;
    const int MINUS = 1;
    const int OVER = 2;
    const int UNDER = 3;
}
