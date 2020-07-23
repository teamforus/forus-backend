<?php

namespace App\Console\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

use App\Models\Fund;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;

class UpdateVoucherTransactionDetailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.bunq.voucher_transactions:update
                            {fund_id : fund id to process.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates voucher transaction details';

    /**
     * Execute the console command.
     *
     * return void
     */
    public function handle() : void
    {
        if (!is_numeric($this->argument('fund_id'))) {
            $this->error("Invalid argument `fund_id`\n");
            exit();
        }

        $this->processTransactionDetailsForFund(Fund::findOrFail((int)($this->argument('fund_id'))));
    }

    /**
     * @param Fund $fund
     * @return VoucherTransaction|Builder|\Illuminate\Database\Query\Builder
     */
    private function getTransactionsWithoutIbanForFund(Fund $fund) {
        return VoucherTransaction::whereHas('voucher', static function(
            Builder $builder
        ) use ($fund) {
            $builder->where('fund_id', $fund->id);
        })->whereNull('iban_from')->orWhereNull('iban_to')->get();
    }

    /**
     * @param $voucher_transactions
     * @param Fund $fund
     * @param BunqService $bunq
     * @return array
     */
    private function updateTransactionDetails($voucher_transactions, Fund $fund, BunqService $bunq) : array {
        $failed_transactions = [];

        /** @var VoucherTransaction $transaction */
        foreach ($voucher_transactions as $index => $transaction) {
            if (!$transaction->payment_id) {
                echo sprintf("Missing payment_id for transaction %s\n", $index);
                continue;
            }

            try {
                if ($payment_details = $bunq->paymentDetails($transaction->payment_id)) {
                    $transaction->update([
                        'iban_from'     => $payment_details->getAlias()->getIban(),
                        'iban_to'       => $payment_details->getCounterpartyAlias()->getIban(),
                        'payment_time'  => $payment_details->getCreated()
                    ]);

                    echo sprintf("Transaction %d processed for fund %s\n", $index + 1, $fund->name);
                }

                sleep(1);
            } catch (\Exception $e) {
                $failed_transactions[] = $transaction->payment_id;
                echo sprintf("Could not process payment %s\n", $transaction->payment_id);
            }
        }

        echo sprintf("All transactions processed for fund %s ", $fund->name);

        return $failed_transactions;
    }

    /**
     * @param Fund $fund
     * return void
     */
    private function processTransactionDetailsForFund(Fund $fund) : void {
        if (!$bunq = $fund->getBunq()) {
            echo sprintf("Could not get bunq context for fund %s\n", $fund->name);
            return;
        }

        $voucher_transactions = $this->getTransactionsWithoutIbanForFund($fund);

        if (!$voucher_transactions->count()) {
            echo sprintf("No unprocessed transactions for fund %s\n", $fund->name);
            return;
        }

        echo sprintf("Processing %d transactions for fund %s\n", $voucher_transactions->count(), $fund->name);

        $failed_transactions = $this->updateTransactionDetails($voucher_transactions, $fund, $bunq);

        if (!count($failed_transactions)) {
            echo "(no failed transactions)\n";
        } else {
            echo sprintf("(%d failed transactions for payments %s)\n",
                count($failed_transactions), implode(', ', $failed_transactions)
            );
        }
    }
}
