<?php

namespace App\Console\Commands\Digests;

use App\Digests\ProviderFundsDigest;
use Illuminate\Console\Command;

class SendProviderFundsDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest:provider_funds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send provider funds digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ProviderFundsDigest::dispatchSync();
    }
}
