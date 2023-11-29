<?php

namespace App\Services\MollieService\Commands;

use App\Services\MollieService\Models\MollieConnection;

class UpdateCompletedMollieConnectionsCommand extends BaseUpdateMollieConnectionsCommand
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
        $this->updateConnections(MollieConnection::ONBOARDING_STATE_COMPLETED);
    }
}
