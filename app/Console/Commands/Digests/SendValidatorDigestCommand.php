<?php

namespace App\Console\Commands\Digests;

use App\Digests\ValidatorDigest;
use Illuminate\Console\Command;

class SendValidatorDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest:validator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send validator digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ValidatorDigest::dispatchSync();
    }
}
