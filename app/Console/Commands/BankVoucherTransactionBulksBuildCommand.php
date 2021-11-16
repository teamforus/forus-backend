<?php

namespace App\Console\Commands;

use App\Models\VoucherTransactionBulk;
use Illuminate\Console\Command;

class BankVoucherTransactionBulksBuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:bulks-build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build voucher transaction bulks.';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handle(): void
    {
        VoucherTransactionBulk::buildBulks();
    }
}
