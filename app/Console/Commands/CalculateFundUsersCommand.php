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
    protected $description = 'Calculate users connected to fund';

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
            Fund::calculateUsersQueue();
        } catch (\Exception $e) {}
    }
}
