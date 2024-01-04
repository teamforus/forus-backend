<?php

namespace App\Services\MollieService\Commands;

use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\MollieServiceLogger;
use Illuminate\Console\Command;
use Throwable;

abstract class BaseUpdateMollieConnectionsCommand extends Command
{
    /**
     * @param string|array $state
     * @return void
     */
    public function updateConnections(string|array $state): void
    {
        $connections = MollieConnection::query()
            ->where('connection_state', MollieConnection::STATE_ACTIVE)
            ->whereIn('onboarding_state', (array) $state)
            ->get();

        foreach ($connections as $connection) {
            try {
                $connection->fetchAndUpdateConnection();
            }  catch (Throwable $e) {
                MollieServiceLogger::logError("Failed to update [$state] mollie connection [$connection->id].", $e);
            }
        }
    }
}
