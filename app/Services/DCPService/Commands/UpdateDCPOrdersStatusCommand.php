<?php

namespace App\Services\DCPService\Commands;

use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Console\Command;

class UpdateDCPOrdersStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcp:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of the orders sent to DCP.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!DCPOrder::isEnabled()) {
            return;
        }

        DCPOrder::queryToUpdateStatus()
            ->with('dcp_connection')
            ->lazyById()
            ->each(fn (DCPOrder $order) => $order->updateStatusSafe());
    }
}
