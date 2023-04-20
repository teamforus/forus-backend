<?php

namespace App\Console\Commands\Digests;

use App\Digests\RequesterDigest;
use Illuminate\Console\Command;

/**
 * Class SendRequesterDigestCommand
 * @package App\Console\Commands
 */
class SendRequesterDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest:requester';

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
        RequesterDigest::dispatchSync();
    }
}
