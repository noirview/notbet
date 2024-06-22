<?php

namespace Database\Factories;

use App\Models\Bet;
use App\Models\Surebet;
use App\Models\SurebetBet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SurebetBetFactory extends Factory
{
    protected $model = SurebetBet::class;

    public function getUpdatedAt(): Carbon
    {
        return Carbon::now();
    }

    public function getCreatedAt(): Carbon
    {
        return Carbon::now();
    }

    public function definition()
    {
        return [
            'updated_at' => $this->getUpdatedAt(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
