<?php

namespace App\Services\MollieService\Commands;

use App\Services\MollieService\Models\MollieConnection;

class UpdatePendingMollieConnectionsCommand extends BaseUpdateMollieConnectionsCommand
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
        $this->updateConnections(MollieConnection::PENDING_ONBOARDING_STATES);
    }
}
