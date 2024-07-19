<?php

namespace App\Contracts;

interface SyncServiceContract
{
    public function sync(SyncSourceContract $source): void;
}
