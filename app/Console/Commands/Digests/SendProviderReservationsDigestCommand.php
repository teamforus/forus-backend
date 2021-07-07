<?php

namespace App\Console\Commands\Digests;

use App\Digests\ProviderReservationsDigest;
use Illuminate\Console\Command;

/**
 * Class SendProviderReservationsDigestCommand
 * @package App\Console\Commands\Digests
 */
class SendProviderReservationsDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.provider_reservations:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send provider reservations digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ProviderReservationsDigest::dispatchNow();
    }
}
