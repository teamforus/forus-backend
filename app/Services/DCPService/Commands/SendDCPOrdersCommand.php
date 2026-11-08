<?php

namespace App\Services\DCPService\Commands;

use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Console\Command;

class SendDCPOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcp:send-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send DCP orders approved in a bulk in the dashboard to DCP.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!DCPOrder::isEnabled()) {
            return;
        }

        $result = DCPOrder::sendAll();

        if ($result === null) {
            $this->error(trans('dcp.errors.sending'));

            return;
        }

        $this->info("Sent: {$result['sent']}, failed: {$result['failed']}.");
    }
}
