<?php

namespace App\Services\BackofficeApiService\Commands;

use App\Services\BackofficeApiService\BackofficeApi;
use Illuminate\Console\Command;

/**
 * Class SendBackofficeLogsCommand
 * @package App\Services\BackofficeApiService\Commands
 */
class SendBackofficeLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'funds.backoffice:send-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send backoffice logs.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        BackofficeApi::sendLogs();
    }
}
