<?php

namespace App\Console\Commands\Digests;

use App\Digests\SponsorProductUpdatesDigest;
use Illuminate\Console\Command;

class SendSponsorProductsUpdateDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.sponsor_products_update_digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send products update digest';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        SponsorProductUpdatesDigest::dispatchSync();
    }
}
