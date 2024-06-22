<?php

namespace Database\Factories;

use App\Enums\EventPlayer\PositionNumber;
use App\Enums\EventPlayer\TeamNumber;
use App\Models\EventPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EventPlayerFactory extends Factory
{
    protected $model = EventPlayer::class;

    public function getTeamNumber(): int
    {
        return TeamNumber::getRandomValue();
    }

    public function getPositionNumber(): int
    {
        return PositionNumber::getRandomValue();
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
            'team_number' => $this->getTeamNumber(),
            'position_number' => $this->getPositionNumber(),
            'updated_at' => $this->getUpdatedAt(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
