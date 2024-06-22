<?php

namespace Database\Factories;

use App\Enums\Bookmaker;
use App\Models\BookmakerTournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookmakerTournamentFactory extends Factory
{
    protected $model = BookmakerTournament::class;

    public function getExternalId(): string
    {
        return $this->faker->randomNumber(8);
    }

    public function getName(): string
    {
        return 'BookmakerTournament';
    }

    public function getBookmaker(): int
    {
        return Bookmaker::getRandomValue();
    }

    public function getCreatedAt(): Carbon
    {
        return Carbon::now();
    }

    public function getUpdatedAt(): Carbon
    {
        return Carbon::now();
    }

    public function definition(): array
    {
        return [
            'external_id' => $this->getExternalId(),
            'name' => $this->getName(),
            'bookmaker' => $this->getBookmaker(),
            'updated_at' => $this->getUpdatedAt(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
