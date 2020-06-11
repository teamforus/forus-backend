<?php

namespace App\Console\Commands;

use App\Digests\ValidatorDigest;
use Illuminate\Console\Command;

class SendValidatorDailyDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.digest.validator:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily validator digest.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        ValidatorDigest::dispatchNow();
    }
}
