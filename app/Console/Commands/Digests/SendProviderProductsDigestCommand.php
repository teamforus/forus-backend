<?php

namespace App\Console\Commands\Digests;

use App\Digests\ProviderProductsDigest;
use Illuminate\Console\Command;

class SendProviderProductsDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest:provider_products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send provider products digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ProviderProductsDigest::dispatchSync();
    }
}
