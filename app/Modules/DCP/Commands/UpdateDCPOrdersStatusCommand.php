<?php

namespace App\Modules\DCP\Commands;

use App\Modules\DCP\Services\DCPOrderProcessingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class UpdateDCPOrdersStatusCommand extends Command
{
    protected $signature = 'dcp:update-status';

    protected $description = 'Fetch the status of accepted DCP orders.';

    /**
     * @param DCPOrderProcessingService $service
     * @return int
     */
    public function handle(DCPOrderProcessingService $service): int
    {
        if (Config::get('dcp.disabled_commands.dcp:update-status', false)) {
            $this->warn('This command is disabled by DCP configuration.');

            return self::SUCCESS;
        }

        $service->updateStatuses();

        return self::SUCCESS;
    }
}
