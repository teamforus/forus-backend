<?php

namespace App\Models\Enums;

use App\Models\DemoTransaction;

enum DemoTransactionState: string
{
    case Pending = DemoTransaction::STATE_PENDING;
    case Accepted = DemoTransaction::STATE_ACCEPTED;
    case Rejected = DemoTransaction::STATE_REJECTED;

    /**
     * @param DemoTransaction $transaction
     * @return self
     */
    public static function forTransaction(DemoTransaction $transaction): self
    {
        return self::from($transaction->state);
    }
}
