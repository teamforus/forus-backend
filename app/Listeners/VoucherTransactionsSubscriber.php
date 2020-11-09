<?php

namespace App\Listeners;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Models\Voucher;
use App\Notifications\Identities\Voucher\IdentityProductVoucherTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherSubsidyTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherTransactionNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class VoucherTransactionsSubscriber
 * @package App\Listeners
 */
class VoucherTransactionsSubscriber
{
    /**
     * @param VoucherTransactionCreated $voucherTransactionEvent
     */
    public function onVoucherTransactionCreated(
        VoucherTransactionCreated $voucherTransactionEvent
    ): void {
        $transaction = $voucherTransactionEvent->getVoucherTransaction();
        $voucher = $transaction->voucher;
        $fund = $transaction->voucher->fund;
        $product = $voucher->product;

        if ($product) {
            $voucher->product->updateSoldOutState();
        }

        $eventMeta = [
            'fund'        => $voucher->fund,
            'voucher'     => $voucher,
            'sponsor'     => $voucher->fund->organization,
            'transaction' => $transaction,
            'provider'    => $transaction->provider,
            'product'     => $transaction->product,
        ];

        if ($voucher->isProductType()) {
            IdentityProductVoucherTransactionNotification::send(
                $voucher->log(Voucher::EVENT_TRANSACTION_PRODUCT, $eventMeta));
        } else if ($voucher->fund->isTypeBudget()) {
            IdentityVoucherTransactionNotification::send(
                $voucher->log(Voucher::EVENT_TRANSACTION, $eventMeta));
        } else if ($voucher->fund->isTypeSubsidy()) {
            $fundProviderProduct = $transaction->product->getSubsidyDetailsForFund($fund);

            if ($fundProviderProduct && $transaction->voucher->identity_address) {
                $eventLog = $voucher->log(Voucher::EVENT_TRANSACTION_SUBSIDY, $eventMeta, [
                    'subsidy_new_limit' => $fundProviderProduct->stockAvailableForIdentity(
                        $transaction->voucher->identity_address
                    )
                ]);

                IdentityVoucherSubsidyTransactionNotification::send($eventLog);
            }
        }

        $transaction->sendPushNotificationTransaction();
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            VoucherTransactionCreated::class,
            '\App\Listeners\VoucherTransactionsSubscriber@onVoucherTransactionCreated'
        );
    }
}
