<?php

namespace Database\Factories;

use App\Enums\Bookmaker;
use App\Models\BookmakerEvent;
use DateTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookmakerEventFactory extends Factory
{
    protected $model = BookmakerEvent::class;

    public function getExternalId(): string
    {
        return $this->faker->randomNumber(8);
    }

    public function getStartAt(): DateTime
    {
        return $this->faker->dateTimeBetween('now', '1 week');
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
            'external_id' => $this->getExternalId(),
            'start_at' => $this->getStartAt(),
            'bookmaker' => $this->getBookmaker(),
            'updated_at' => $this->getUpdatedAt(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
