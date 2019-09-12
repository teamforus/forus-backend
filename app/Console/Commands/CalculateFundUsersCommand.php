<?php

namespace App\Console\Commands;

use App\Models\Fund;
use Illuminate\Console\Command;

class CalculateFundUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.fund.users:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send users statistic report.';

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
        if (!$email = env('EMAIL_FOR_FUND_CALC', 'demo@forus.io')) {
            return;
        }

        try {
            Fund::sendUserStatisticsReport($email);
        } catch (\Exception $e) {}
    }
}
