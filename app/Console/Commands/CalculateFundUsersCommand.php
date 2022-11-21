<?php

namespace App\Console\Commands;

use App\Models\Fund;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

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
    public function handle(): void
    {
        if (!$email = Config::get('forus.notification_mails.fund_calc', false)) {
            return;
        }

        try {
            Fund::sendUserStatisticsReport($email);
        } catch (Throwable) {}
    }
}
