<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface SyncSourceContract
{
    public function tournaments(): Collection;

    public function events(): Collection;

    public function players(): Collection;

    public function eventPlayers(): Collection;

    public function bets(): Collection;
}
