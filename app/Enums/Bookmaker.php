<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class Bookmaker extends Enum implements LocalizedEnum
{
    const int MARATHONBET = 0;
    const int MAXLINE = 1;
    const int THREE = 2;
}
