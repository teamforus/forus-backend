<?php

namespace App\Listeners;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Models\Voucher;
use Illuminate\Events\Dispatcher;

class VoucherTransactionsSubscriber
{
    /**
     * @param VoucherTransactionCreated $voucherTransactionEvent
     */
    public function onVoucherTransactionCreated(
        VoucherTransactionCreated $voucherTransactionEvent
    ) {
        $transaction = $voucherTransactionEvent->getVoucherTransaction();
        $voucher = $transaction->voucher;

        if ($voucher->type == Voucher::TYPE_PRODUCT && $voucher->product) {
            $voucher->product->updateSoldOutState();
        } else {
            $voucher->sendEmailAvailableAmount();
        }

        $transaction->sendPushNotificationTransaction();
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            VoucherTransactionCreated::class,
            '\App\Listeners\VoucherTransactionsSubscriber@onVoucherTransactionCreated'
        );
    }
}
