<?php

namespace App\Listeners;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Models\Voucher;
use App\Notifications\Identities\Voucher\IdentityProductVoucherTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherTransactionNotification;
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
        $isProductTransaction = $voucher->type == Voucher::TYPE_PRODUCT;

        if ($isProductTransaction) {
            $voucher->product->updateSoldOutState();
            $eventLog = $voucher->log(Voucher::EVENT_TRANSACTION_PRODUCT, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
                'provider' => $transaction->provider,
                'product' => $voucher->product,
            ]);

            IdentityProductVoucherTransactionNotification::send($eventLog);
        } else {
            $voucher->sendEmailAvailableAmount();
            $eventLog = $voucher->log(Voucher::EVENT_TRANSACTION, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
                'provider' => $transaction->provider,
            ]);

            IdentityVoucherTransactionNotification::send($eventLog);
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
