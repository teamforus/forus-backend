<?php

namespace App\Services\BunqService\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

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
            BunqService::processQueue();
        } catch (\Exception $e) {}
    }
}
