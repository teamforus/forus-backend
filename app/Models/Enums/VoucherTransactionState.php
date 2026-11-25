<?php

namespace App\Models\Enums;

use App\Models\VoucherTransaction;

enum VoucherTransactionState: string
{
    case Pending = VoucherTransaction::STATE_PENDING;
    case Success = VoucherTransaction::STATE_SUCCESS;
    case Canceled = VoucherTransaction::STATE_CANCELED;

    /**
     * @param VoucherTransaction $transaction
     * @return self
     */
    public static function forTransaction(VoucherTransaction $transaction): self
    {
        return self::from($transaction->state);
    }
}
