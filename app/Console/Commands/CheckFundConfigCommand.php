<?php

namespace App\Console\Commands;

use App\Models\Fund;
use Illuminate\Console\Command;

class CheckFundConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.fund.config:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
        'Check if fund is configured ' .
        'and send email to approved providers about `fund started`';

    /**
     * Execute the console command.
     *
     * @param Fund $fund
     * @return void
     */
    public function handle(Fund $fund): void
    {
        // TODO: check this command
        /*try {
            $fund::checkConfigStateQueue();
        } catch (\Exception $e) {}*/
    }
}
