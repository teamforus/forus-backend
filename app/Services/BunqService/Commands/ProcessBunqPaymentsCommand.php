<?php

namespace App\Services\BunqService\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

/**
 * Class ProcessBunqPaymentsCommand
 * @package App\Services\BunqService\Commands
 */
class ProcessBunqPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.bunq:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process bunq transactions queue.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        try {
            BunqService::processQueue();
        } catch (\Exception $e) {
            logger()->debug(sprintf("Failed to process bunq transactions: %s", $e->getMessage()));
        }
    }
}
