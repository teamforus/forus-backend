<?php

namespace App\Services\MollieService\Commands;

use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Console\Command;

class UpdatePendingMollieConnectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mollie:update-pending-connections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pending onboarding molli connections.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        MollieConnection::query()
            ->where('connection_state', MollieConnection::CONNECTION_STATE_ACTIVE)
            ->whereIn('onboarding_state', MollieConnection::PENDING_ONBOARDING_STATES)
            ->get()
            ->each(function (MollieConnection $mollieConnection) {
                try {
                    $mollieConnection->fetchAndUpdateConnection();
                } catch (MollieApiException $e) {}
            });
    }
}
