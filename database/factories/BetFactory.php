<?php

namespace Database\Factories;

use App\Enums\Bet\NumberPeriod;
use App\Enums\Bet\NumberTeam;
use App\Enums\Bet\Sign;
use App\Enums\Bet\Type;
use App\Enums\Bookmaker;
use App\Models\Bet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BetFactory extends Factory
{
    protected $model = Bet::class;

    public function getType(): int
    {
        return Type::getRandomValue();
    }

    public function getNumberTeam(): int|null
    {
        $isNull = $this->faker->boolean(25);
        if ($isNull) {
            return null;
        }

        return NumberTeam::getRandomValue();
    }

    public function getNumberPeriod(): int|null
    {
        $isNull = $this->faker->boolean(25);
        if ($isNull) {
            return null;
        }

        return NumberPeriod::getRandomValue();
    }

    public function getSign(): int|null
    {
        $isNull = $this->faker->boolean(66);
        if ($isNull) {
            return null;
        }

        return Sign::getRandomValue();
    }

    public function getValue(): float|null
    {
        $isNull = $this->faker->boolean(66);
        if ($isNull) {
            return null;
        }

        return $this->faker->randomFloat(2, 0, 20);
    }

    public function getCoefficient(): float
    {
        return $this->faker->randomFloat(3, 1.05, 20);
    }

    public function getBookmaker(): int
    {
        return Bookmaker::getRandomValue();
    }

    public function getUpdatedAt(): Carbon
    {
        return Carbon::now();
    }

    public function getCreatedAt(): Carbon
    {
        return Carbon::now();
    }

    public function definition(): array
    {
        return [
            'type' => $this->getType(),
            'number_team' => $this->getNumberTeam(),
            'number_period' => $this->getNumberPeriod(),
            'sign' => $this->getSign(),
            'value' => $this->getValue(),
            'coefficient' => $this->getCoefficient(),
            'bookmaker' => $this->getBookmaker(),
            'updated_at' => $this->getUpdatedAt(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
