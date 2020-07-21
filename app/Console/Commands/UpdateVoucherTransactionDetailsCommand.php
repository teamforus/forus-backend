<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

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
    protected $signature = 'forus.digest.voucher_transactions:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates voucher transaction details';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /** @var Collection|Fund[] $funds */
        $funds = Fund::whereHas('vouchers.transactions', static function(
            Builder $builder
        ) {
            $builder->whereNull('iban_from');
            $builder->orWhereNull('iban_to');
        })->get();

        foreach ($funds as $fund) {
            self::updateTransactionDetailsForFund($fund);
        }
    }

    /**
     * @param Fund $fund
     * @return VoucherTransaction|Builder|\Illuminate\Database\Query\Builder
     */
    public static function getUnprocessedTransactionsForFund(Fund $fund) {
        return VoucherTransaction::whereHas('voucher', static function(
            Builder $builder
        ) use ($fund) {
            $builder->where('fund_id', $fund->id);
        })->whereNull('iban_from')->orWhereNull('iban_to')->get();
    }

    /**
     * @param Fund $fund
     */
    public static function updateTransactionDetailsForFund(Fund $fund) {
        if (!$bunq = $fund->getBunq()) {
            return;
        }

        $voucher_transactions = self::getUnprocessedTransactionsForFund($fund);

        /** @var VoucherTransaction $transaction */
        foreach ($voucher_transactions as $transaction) {
            try {
                if ($payment_details = $bunq->paymentDetails($transaction->payment_id)) {
                    $transaction->forceFill([
                        'iban_from' => $payment_details->getAlias()->getIban(),
                        'iban_to'   => $payment_details->getCounterpartyAlias()->getIban(),
                    ])->save();
                }

                sleep(1);
            } catch (\Exception $e) {
                logger()->error(sprintf(
                    'Could not process payment: %s', $transaction->payment_id
                ));
            }
        }
    }
}
