<?php

namespace App\Console\Commands;

use App\Digests\RequesterDigest;
use Illuminate\Console\Command;

class SendRequesterDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.requester:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send requester digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        RequesterDigest::dispatchNow();
    }
}
