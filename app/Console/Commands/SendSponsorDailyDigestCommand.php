<?php

namespace App\Console\Commands;

use App\Digests\SponsorDigest;
use Illuminate\Console\Command;

class SendSponsorDailyDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.sponsor:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily sponsor digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        SponsorDigest::dispatchNow();
    }
}
