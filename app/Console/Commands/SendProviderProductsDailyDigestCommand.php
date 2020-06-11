<?php

namespace App\Console\Commands;

use App\Digests\ProviderProductsDigest;
use Illuminate\Console\Command;

class SendProviderProductsDailyDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.provider_products:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily provider products digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ProviderProductsDigest::dispatchNow();
    }
}
