<?php

namespace App\Services\Bookmakers;

use App\Contracts\SyncSourceContract;
use App\Enums\Bookmaker;
use App\Services\Bookmakers\Marathonbet\MarathonbetService;
use App\Services\Bookmakers\Maxline\MaxlineService;
use Illuminate\Support\Collection;

class BookmakerService implements SyncSourceContract
{
    private array $container = [];

    public function __construct() {
        $this->container[Bookmaker::MARATHONBET] = new MarathonbetService();
        $this->container[Bookmaker::MAXLINE] = new MaxlineService();
    }

    public function parse()
    {
        foreach ($this->container as $service) {
            $service->parse();
        }
    }

    public function tournaments(): Collection
    {
        $result = collect();
        /** @var SyncSourceContract $service */
        foreach ($this->container as $service) {
            $result = $result->merge($service->tournaments());
        }

        return $result;
    }

    public function events(): Collection
    {
        $result = collect();
        /** @var SyncSourceContract $service */
        foreach ($this->container as $service) {
            $result = $result->merge($service->events());
        }

        return $result;
    }

    public function players(): Collection
    {
        $result = collect();
        /** @var SyncSourceContract $service */
        foreach ($this->container as $service) {
            $result = $result->merge($service->players());
        }

        return $result;
    }

    public function eventPlayers(): Collection
    {
        $result = collect();
        /** @var SyncSourceContract $service */
        foreach ($this->container as $service) {
            $result = $result->merge($service->eventPlayers());
        }

        return $result;
    }

    public function bets(): Collection
    {
        $result = collect();
        /** @var SyncSourceContract $service */
        foreach ($this->container as $service) {
            $result = $result->merge($service->bets());
        }

        return $result;
    }
}
