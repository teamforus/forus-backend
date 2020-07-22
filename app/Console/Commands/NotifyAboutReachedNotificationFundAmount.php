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
     * Execute the console command.
     *
     * @param Fund $fund
     * @return void
     */
    public function handle(Fund $fund): void
    {
        try {
            $fund::notifyAboutReachedNotificationAmount();
        } catch (\Exception $e) {}
    }
}
