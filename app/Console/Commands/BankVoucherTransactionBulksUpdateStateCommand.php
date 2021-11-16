<?php

namespace App\Console\Commands;

use App\Models\VoucherTransactionBulk;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BankVoucherTransactionBulksUpdateStateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:bulks-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch bank api to update bulk transactions state.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        /** @var VoucherTransactionBulk[]|Collection $bulks */
        $bulks = VoucherTransactionBulk::whereState(VoucherTransactionBulk::STATE_PENDING)->get();

        foreach ($bulks as $transactionBulk) {
            try {
                $transactionBulk->bank_connection->useContext();
                $transactionBulk->update([
                    'state_fetched_times'   => $transactionBulk->state_fetched_times + 1,
                    'state_fetched_at'      => now(),
                ]);

                $payment = $transactionBulk->fetchPayment();

                switch (strtolower($payment->getStatus())) {
                    case $transactionBulk::STATE_REJECTED: $transactionBulk->setRejected(); break;
                    case $transactionBulk::STATE_ACCEPTED: $transactionBulk->setAccepted($payment); break;
                }
            } catch (Throwable $e) {
                logger()->error($e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }
    }
}
