<?php

namespace App\Console\Commands;

use App\Models\Fund;
use Illuminate\Console\Command;

class NotifyAboutReachedNotificationFundAmount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.fund:check-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if budget left reached notification amount';

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
     * @return mixed
     */
    public function handle()
    {
        try {
            Fund::notifyAboutReachedNotificationAmountQueue();
        } catch (\Exception $e) {}
    }
}
