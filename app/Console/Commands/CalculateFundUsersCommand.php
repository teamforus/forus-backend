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
}
