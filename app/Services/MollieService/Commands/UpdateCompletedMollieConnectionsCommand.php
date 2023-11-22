<?php

namespace App\Services\MollieService\Commands;

use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\MollieService;
use Illuminate\Console\Command;
use Throwable;

class UpdateCompletedMollieConnectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mollie:update-completed-connections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update completed mollie connections.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        MollieConnection::query()
            ->where('connection_state', MollieConnection::CONNECTION_STATE_ACTIVE)
            ->where('onboarding_state', MollieConnection::ONBOARDING_STATE_COMPLETED)
            ->get()
            ->each(function (MollieConnection $mollieConnection) {
                try {
                    $mollieConnection->fetchAndUpdateConnection();
                }  catch (Throwable $e) {
                    MollieService::logError(sprintf(
                        'Failed to update a complete mollie connection [%s].',
                        $mollieConnection->id,
                    ), $e);
                }
            });
    }
}
