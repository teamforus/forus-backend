<?php

namespace App\Console\Commands;

use App\Models\VoucherTransaction;
use Illuminate\Console\Command;

class BankVoucherTransactionProcessZeroAmountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:process-zero-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process voucher transactions with the amount of 0 (meant for reservations).';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handle(): void
    {
        VoucherTransaction::processZeroAmount();
    }
}
