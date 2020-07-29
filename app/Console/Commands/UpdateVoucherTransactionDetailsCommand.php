<?php

namespace App\Console\Commands;

use App\Services\BunqService\BunqService;
use Illuminate\Console\Command;

use App\Models\Fund;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
        $fund = Fund::find($this->argument('fund_id'));

        if (!$fund) {
            $this->error("Invalid argument `fund_id`\n");
        } else {
            $this->processTransactionDetailsForFund($fund);
        }
    }

    /**
     * @param Fund $fund
     * @return VoucherTransaction[]|Builder|\Illuminate\Database\Query\Builder
     */
    private function getTransactionsWithoutIbanForFund(Fund $fund) {
        return VoucherTransaction::whereHas('voucher', static function(
            Builder $builder
        ) use ($fund) {
            $builder->where('fund_id', $fund->id);
        })->where(static function(Builder $builder) {
            $builder->whereNull('iban_from');
            $builder->orWhereNull('iban_to');
        })->whereNotNull('payment_id')->get();
    }

    /**
     * @param VoucherTransaction[]|Collection $voucher_transactions
     * @param Fund $fund
     * @param BunqService $bunq
     * @return array
     */
    private function updateTransactionDetails(
        $voucher_transactions,
        Fund $fund,
        BunqService $bunq
    ) : array {
        $failed_transactions = [];

        foreach ($voucher_transactions as $index => $transaction) {
            try {
                if ($payment_details = $bunq->paymentDetails($transaction->payment_id)) {
                    $transaction->update([
                        'iban_from'     => $payment_details->getAlias()->getIban(),
                        'iban_to'       => $payment_details->getCounterpartyAlias()->getIban(),
                        'payment_time'  => $payment_details->getCreated()
                    ]);

                    $this->info(sprintf(
                        "Transaction %d from %d processed for fund %s\n",
                        $index + 1,
                        $voucher_transactions->count(),
                        $fund->name
                    ));
                }

                sleep(1);
            } catch (\Throwable $e) {
                $failed_transactions[] = $transaction->id;
                $this->error(sprintf(
                    "Could not process transaction %s with 'payment_id' %s.",
                    $transaction->id,
                    is_null($transaction->payment_id) ? 'null' : $transaction->payment_id
                ));
            }
        }

        $this->comment(sprintf("All transactions processed for fund %s ", $fund->name));

        return $failed_transactions;
    }

    /**
     * @param Fund $fund
     * return void
     */
    private function processTransactionDetailsForFund(Fund $fund) : void {
        if (!$bunq = $fund->getBunq()) {
            $this->error(sprintf("Could not get bunq context for fund %s\n.", $fund->name));
            return;
        }

        $voucher_transactions = $this->getTransactionsWithoutIbanForFund($fund);

        if ($voucher_transactions->count() === 0) {
            $this->info(sprintf("No unprocessed transactions for fund %s.\n", $fund->name));
            return;
        }

        $this->info(sprintf(
            "Processing %d transactions for fund %s\n",
            $voucher_transactions->count(),
            $fund->name
        ));

        $failed_transactions = $this->updateTransactionDetails($voucher_transactions, $fund, $bunq);

        if (!count($failed_transactions)) {
            $this->info("(no failed transactions)\n");
        } else {
            $this->error(sprintf(
                "(%d failed transactions for payments %s)\n",
                count($failed_transactions),
                implode(', ', $failed_transactions)
            ));
        }
    }
}
