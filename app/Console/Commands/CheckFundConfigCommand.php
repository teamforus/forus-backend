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
    protected $description = 'Check if fund is configured';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Fund::checkConfigStateQueue();
        } catch (\Exception $e) {}
    }
}
