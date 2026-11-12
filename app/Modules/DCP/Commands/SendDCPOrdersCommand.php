<?php

namespace App\Modules\DCP\Commands;

use App\Modules\DCP\Services\DCPOrderProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class SendDCPOrdersCommand extends Command
{
    protected $signature = 'dcp:send-orders';

    protected $description = 'Send approved DCP orders.';

    /**
     * @param DCPOrderProcessingService $service
     * @return int
     */
    public function handle(DCPOrderProcessingService $service): int
    {
        if (Config::get('dcp.disabled_commands.dcp:send-orders', false)) {
            $this->warn('This command is disabled by DCP configuration.');

            return self::SUCCESS;
        }

        $service->sendApprovedOrders();

        return self::SUCCESS;
    }
}
