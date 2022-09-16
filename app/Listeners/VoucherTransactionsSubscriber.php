<?php

namespace App\Listeners;

use App\Events\VoucherTransactions\VoucherTransactionBunqSuccess;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Models\FundProvider;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Notifications\Identities\Voucher\IdentityProductVoucherTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherSubsidyTransactionNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherBudgetTransactionNotification;
use App\Notifications\Organizations\FundProviders\FundProviderTransactionBunqSuccessNotification;
use Illuminate\Events\Dispatcher;

class VoucherTransactionsSubscriber
{
    /**
     * @param VoucherTransactionCreated $event
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function onVoucherTransactionCreated(VoucherTransactionCreated $event): void
    {
        $transaction = $event->getVoucherTransaction();
        $voucher = $transaction->voucher;
        $fund = $transaction->voucher->fund;
        $type = $voucher->isProductType() ? 'product' : ($fund->isTypeBudget() ? 'budget' : 'subsidy');
        $logData = $event->getLogData();

        if (!$voucher->parent_id && $voucher->usedCount() == 1) {
            $voucher->reportBackofficeFirstUse();
        }

        $transaction->product?->updateSoldOutState();

        $eventMeta = [
            'fund'                  => $voucher->fund,
            'voucher'               => $voucher,
            'sponsor'               => $voucher->fund->organization,
            'provider'              => $transaction->provider,
            'product'               => $transaction->product,
            'voucher_transaction'   => $transaction,
            'implementation'        => $fund->getImplementation(),
        ];

        if ($type == 'product') {
            $event = $voucher->log(Voucher::EVENT_TRANSACTION_PRODUCT, $eventMeta, $logData);
            IdentityProductVoucherTransactionNotification::send($event);
        } elseif ($type == 'budget') {
            $event = $voucher->log(Voucher::EVENT_TRANSACTION, $eventMeta, $logData);

            if ($transaction->isOutgoing()) {
                IdentityVoucherBudgetTransactionNotification::send($event);
            }
        } elseif ($type == 'subsidy') {
            $fundProviderProduct = $transaction->product->getSubsidyDetailsForFund($fund);

            if ($fundProviderProduct) {
                $eventLog = $voucher->log(Voucher::EVENT_TRANSACTION_SUBSIDY, $eventMeta, array_merge([
                    'subsidy_new_limit' => $fundProviderProduct->stockAvailableForVoucher($transaction->voucher),
                ], $logData));

                if ($transaction->voucher->identity_address) {
                    IdentityVoucherSubsidyTransactionNotification::send($eventLog);
                }
            }
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
     * The events dispatcher
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
