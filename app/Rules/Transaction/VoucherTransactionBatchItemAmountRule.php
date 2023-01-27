<?php

namespace App\Rules\Transaction;

use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\BaseRule;

class VoucherTransactionBatchItemAmountRule extends BaseRule
{
    /**
     * @param Organization $organization
     * @param array $transactions
     */
    public function __construct(
        protected Organization $organization,
        protected array $transactions = [],
    ) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // get current transaction index
        $index = (int) (explode('.', $attribute)[1] ?? 0);

        /** @var Voucher|null $voucher current row voucher */
        $voucher = $this->transactions[$index]['voucher'] ?? null;
        $amount = $this->transactions[$index]['amount'] ?? 0;

        if (!$voucher) {
            return $this->reject('The voucher was not found or you have no permissions to use the voucher.');
        }

        $transactionToIndex = array_slice($this->transactions, 0, $index);
        $amountToIndex = $this->getAmountToOffsetOld($transactionToIndex, $voucher->id) + $amount;

        if ($voucher->amount_available_cached < $amount) {
            return $this->reject(sprintf(
                'The amount of the transaction (%s) is higher than the balance of the voucher (%s).',
                currency_format_locale($amount),
                currency_format_locale($voucher->amount_available_cached),
            ));
        }

        if ($voucher->amount_available_cached < $amountToIndex) {
            return $this->reject(sprintf(
                'The sum of the transactions from for selected voucher (%s) is higher than the balance of the voucher (%s).',
                currency_format_locale($amountToIndex),
                currency_format_locale($voucher->amount_available_cached),
            ));
        }

        return true;
    }

    /**
     * @param array $transactions
     * @param int $voucherId
     * @return float
     */
    protected function getAmountToOffsetOld(array $transactions, int $voucherId): float
    {
        return array_reduce($transactions, static function (float $total, $transaction) use ($voucherId) {
            return $transaction['voucher_id'] == $voucherId ? $total + $transaction['amount'] : $total;
        }, 0);
    }
}
