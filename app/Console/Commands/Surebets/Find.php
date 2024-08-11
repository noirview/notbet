<?php

namespace App\Console\Commands\Surebets;

use App\Models\Event;
use App\Services\SureBet\SurebetService;
use Illuminate\Console\Command;

class Find extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surebets:find';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(SurebetService $surebet)
    {
        Event::query()
            ->has('bookmakerEvents', '>', 1)
            ->get()
            ->map(fn (Event $event) => $surebet->find($event));
    }
}
