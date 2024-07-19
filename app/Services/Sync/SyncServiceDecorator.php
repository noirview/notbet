<?php

namespace App\Services\Sync;

use App\Contracts\SyncServiceContract;
use App\Contracts\SyncSourceContract;

abstract class SyncServiceDecorator implements SyncServiceContract
{
    public function __construct(
        protected SyncServiceContract $wrappee,
    ) {}

    public abstract function sync(SyncSourceContract $source): void;
}
