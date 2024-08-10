<?php

namespace App\Console\Commands\Events;

use App\Services\Link\EventLinkService;
use App\Services\SpreadSheet\SpreadSheetService;
use Illuminate\Console\Command;

class link extends Command
{
    protected $signature = 'events:manual-link';

    protected $description = 'Command description';

    public function handle(
        SpreadSheetService $spreadSheet,
        EventLinkService $linkService,
    ): void
    {
        $spreadSheet->link($linkService);
    }
}
