<?php

namespace App\Listeners;

use App\Events\VoucherTransactions\VoucherTransactionBunqSuccess;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Mail\Forus\TransactionVerifyMail;
use App\Models\FundProvider;
use App\Models\Voucher;
use App\Notifications\Identities\Voucher\IdentityProductVoucherTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherBudgetTransactionNotification;
use App\Notifications\Organizations\FundProviders\FundProviderTransactionBunqSuccessNotification;
use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Config;

class VoucherTransactionsSubscriber
{
    /**
     * @param VoucherTransactionCreated $event
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function onVoucherTransactionCreated(VoucherTransactionCreated $event): void
    {
        $transaction = $event->getVoucherTransaction();
        $voucher = $transaction->voucher;
        $fund = $transaction->voucher->fund;
        $type = $voucher->isProductType() ? 'product' : 'budget';
        $logData = $event->getLogData();

        if (!$voucher->parent_id && $voucher->usedCount() == 1) {
            $voucher->reportBackofficeFirstUse();
        }

        $transaction->product?->updateSoldOutState();

        $eventMeta = [
            'fund' => $voucher->fund,
            'voucher' => $voucher,
            'sponsor' => $voucher->fund->organization,
            'provider' => $transaction->provider,
            'product' => $transaction->product,
            'voucher_transaction' => $transaction,
            'implementation' => $fund->getImplementation(),
        ];

        if ($type == 'product') {
            $event = $voucher->log(Voucher::EVENT_TRANSACTION_PRODUCT, $eventMeta, $logData);
            IdentityProductVoucherTransactionNotification::send($event);
        }

        if ($type == 'budget') {
            $event = $voucher->log(Voucher::EVENT_TRANSACTION, $eventMeta, $logData);

            if ($transaction->isOutgoing()) {
                IdentityVoucherBudgetTransactionNotification::send($event);
            }
        }

        if ($transaction->attempts >= 50 && Config::get('forus.notification_mails.transaction_verify')) {
            resolve('forus.services.notification')->sendSystemMail(
                Config::get('forus.notification_mails.transaction_verify'),
                new TransactionVerifyMail([
                    'id' => $transaction->id,
                    'fund_name' => $transaction->voucher?->fund?->name,
                ]),
            );
        }
    }

    /**
     * @param VoucherTransactionBunqSuccess $event
     * @noinspection PhpUnused
     */
    public function onVoucherTransactionBunqSuccess(VoucherTransactionBunqSuccess $event): void
    {
        $transaction = $event->getVoucherTransaction();

        if (!$transaction->targetIsProvider() || !$transaction->organization_id) {
            return;
        }

        $fundProvider = $transaction->voucher->fund->providers()->where([
            'organization_id' => $transaction->organization_id,
        ])->first();

        if ($fundProvider) {
            $event = $fundProvider->log(FundProvider::EVENT_BUNQ_TRANSACTION_SUCCESS, [
                'fund' => $transaction->voucher->fund,
                'sponsor' => $transaction->voucher->fund->organization,
                'provider' => $transaction->provider,
                'employee' => $transaction->employee,
                'implementation' => $transaction->voucher->fund->getImplementation(),
                'voucher_transaction' => $transaction,
            ], $event->getLogData());

            FundProviderTransactionBunqSuccessNotification::send($event);
        }
    }

    /**
     * The events dispatcher.
     *
     * @param Dispatcher $events
     * @return void
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(VoucherTransactionCreated::class, "$class@onVoucherTransactionCreated");
        $events->listen(VoucherTransactionBunqSuccess::class, "$class@onVoucherTransactionBunqSuccess");
    }
}
