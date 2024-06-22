<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EventFactory extends Factory
{
    protected $model = Event::class;

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
