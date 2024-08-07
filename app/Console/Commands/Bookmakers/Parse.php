<?php

namespace App\Console\Commands\Bookmakers;

use App\Services\Bookmakers\BookmakerService;
use App\Services\Sync\BetSyncService;
use App\Services\Sync\EventPlayerSyncService;
use App\Services\Sync\EventSyncService;
use App\Services\Sync\PlayerSyncService;
use App\Services\Sync\TournamentSyncService;
use Illuminate\Console\Command;

class Parse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookmakers:parse';

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
        BookmakerService $bookmakers,
    )
    {
        $bookmakers->parse();

        $bss = new BetSyncService();
        $epss = new EventPlayerSyncService($bss);
        $pss = new PlayerSyncService($epss);
        $ess = new EventSyncService($pss);
        $tss = new TournamentSyncService($ess);

        $tss->sync($bookmakers);
    }
}
