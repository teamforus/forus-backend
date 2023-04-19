<?php

namespace App\Console\Commands\Digests;

use App\Digests\ProviderFundsDigest;
use App\Digests\ProviderProductsDigest;
use App\Digests\RequesterDigest;
use App\Digests\SponsorDigest;
use App\Digests\ValidatorDigest;
use Illuminate\Console\Command;

/**
 * Class SendAllDigestsCommand
 * @package App\Console\Commands
 */
class SendAllDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest:all';

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
        ProviderProductsDigest::dispatchSync();
        RequesterDigest::dispatchSync();
        SponsorDigest::dispatchSync();
        ValidatorDigest::dispatchSync();
    }
}
