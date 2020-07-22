<?php

namespace App\Console\Commands;

use App\Models\Fund;
use Illuminate\Console\Command;

class CheckFundStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.fund:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check fund state';

    /**
     * Execute the console command.
     *
     * @param Fund $fund
     * @return void
     */
    public function handle(Fund $fund): void
    {
        try {
            $fund::checkStateQueue();
        } catch (\Exception $e) {}
    }
}
