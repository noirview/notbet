<?php

namespace App\Console\Commands\Tournaments;

use App\Models\BookmakerTournament;
use App\Services\SpreadSheet\SpreadSheetService;
use Illuminate\Console\Command;

class _Print extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tournaments:print';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(SpreadSheetService $service)
    {
        $service->print(
            BookmakerTournament::query()->select(['id', 'name', 'tournament_id', 'bookmaker'])->get(),
            'tournament_id'
        );
    }
}
