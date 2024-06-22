<?php

namespace Database\Factories;

use App\Enums\Bookmaker;
use App\Models\BookmakerPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookmakerPlayerFactory extends Factory
{
    protected $model = BookmakerPlayer::class;

    public function getName(): string
    {
        return $this->faker->name();
    }

    public function getIsShortName(): bool
    {
        return $this->faker->boolean(66);
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
            'name' => $this->getName(),
            'is_short_name' => $this->getIsShortName(),
            'bookmaker' => $this->getBookmaker(),
            'updated_at' => $this->getUpdatedAt(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
