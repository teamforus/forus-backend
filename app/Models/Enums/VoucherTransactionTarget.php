<?php

namespace App\Models\Enums;

use App\Models\VoucherTransaction;

enum VoucherTransactionTarget: string
{
    case Provider = VoucherTransaction::TARGET_PROVIDER;
    case Iban = VoucherTransaction::TARGET_IBAN;
    case TopUp = VoucherTransaction::TARGET_TOP_UP;
    case Payout = VoucherTransaction::TARGET_PAYOUT;

    /**
     * @param VoucherTransaction $transaction
     * @return self
     */
    public static function forTransaction(VoucherTransaction $transaction): self
    {
        return self::from($transaction->target);
    }
}
