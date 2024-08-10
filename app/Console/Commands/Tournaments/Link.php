<?php

namespace App\Console\Commands\Tournaments;

use App\Services\Link\TournamentLinkService;
use App\Services\SpreadSheet\SpreadSheetService;
use Illuminate\Console\Command;

class Link extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tournaments:manual-link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(
        SpreadSheetService $sheetService,
        TournamentLinkService $linkService,
    )
    {
        $sheetService->link($linkService);
    }
}
