<?php

namespace App\Services\BunqService\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

class ProcessBunqSyncIdealIssuersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.bunq:sync_ideal_issuers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bunq ideal issuers list.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        try {
            BunqService::updateIdealSandboxIssuers();
            BunqService::updateIdealProductionIssuers();
        } catch (\Exception $e) {}
    }
}
