<?php

namespace App\Console\Commands;

use App\Digests\ProviderFundsDigest;
use Illuminate\Console\Command;

class SendProviderFundsDailyDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.provider_funds:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily provider funds digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ProviderFundsDigest::dispatchNow();
    }
}
