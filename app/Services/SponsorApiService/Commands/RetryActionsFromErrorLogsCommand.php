<?php

namespace App\Services\SponsorApiService\Commands;

use Illuminate\Console\Command;

class RetryActionsFromErrorLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sponsor.api.actions:retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        resolve('sponsor_api')->retryActionsFromErrorLogs();
    }
}
