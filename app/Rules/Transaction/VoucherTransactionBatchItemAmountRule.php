<?php

namespace App\Rules\Transaction;

use App\Models\Organization;
use App\Models\Voucher;
use App\Rules\BaseRule;

class VoucherTransactionBatchItemAmountRule extends BaseRule
{
    /**
     * @param Organization $organization
     * @param array $transactionData
     */
    public function __construct(
        protected Organization $organization,
        protected array $transactionData = []
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
        $voucher = $this->transactionData[$index]['voucher'] ?? null;
        $amount = $this->transactionData[$index]['amount'] ?? 0;

        if (!$voucher) {
            return $this->reject('Voucher not found');
        }

        return $voucher->amount_available_cached >= $amount || $this->reject('Amount is bigger then available');
    }
}
