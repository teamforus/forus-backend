<?php

namespace App\Console\Commands;

use App\Models\PrevalidationRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ProcessPrevalidationRequestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.prevalidation_requests:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make prevalidations from requests.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $chunkSize = (int) Config::get('forus.prevalidation_requests.process_requests.chunk_size', 100);
        $sleepSeconds = (int) Config::get('forus.prevalidation_requests.process_requests.sleep_seconds', 10);

        PrevalidationRequest::query()
            ->where('state', PrevalidationRequest::STATE_PENDING)
            ->chunkById($chunkSize, function ($requests) use ($sleepSeconds) {
                foreach ($requests as $request) {
                    $request->makePrevalidation();
                }

                if ($sleepSeconds > 0) {
                    sleep($sleepSeconds);
                }
            });
    }
}
