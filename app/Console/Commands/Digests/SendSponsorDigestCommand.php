<?php

namespace App\Console\Commands\Digests;

use App\Digests\SponsorDigest;
use Illuminate\Console\Command;

class SendSponsorDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest:sponsor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send sponsor digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        SponsorDigest::dispatchSync();
    }
}
