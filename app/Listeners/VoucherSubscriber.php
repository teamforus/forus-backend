<?php

namespace App\Listeners;

use App\Events\Products\ProductReserved;
use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Events\Vouchers\VoucherDeactivated;
use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpireSoon;
use App\Events\Vouchers\VoucherPhysicalCardRequestedEvent;
use App\Events\Vouchers\VoucherSendToEmailEvent;
use App\Mail\Vouchers\SendVoucherMail;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Notifications\Identities\Voucher\IdentityVoucherPhysicalCardRequestedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherAddedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherReservedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherSharedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAddedBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAddedSubsidyNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedProductNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedSubsidyNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherDeactivatedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonProductNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherSharedByEmailNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class VoucherSubscriber
 * @package App\Listeners
 */
class VoucherSubscriber
{
    /**
     * @param VoucherCreated $voucherCreated
     * @noinspection PhpUnused
     */
    public function onVoucherCreated(VoucherCreated $voucherCreated): void
    {
        $voucher = $voucherCreated->getVoucher();
        $product = $voucher->product;

        $voucher->tokens()->create([
            'address'           => resolve('token_generator')->address(),
            'need_confirmation' => true,
        ]);

        /** @var VoucherToken $voucherToken */
        $voucher->tokens()->create([
            'address'           => resolve('token_generator')->address(),
            'need_confirmation' => false,
        ]);

        if ($product) {
            $product->updateSoldOutState();
            ProductReserved::dispatch($product, $voucher);

            $event = $voucher->log(Voucher::EVENT_CREATED_PRODUCT, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'product' => $product,
                'provider' => $product->organization,
                'sponsor' => $voucher->fund->organization,
                'employee' => $voucher->employee,
            ]);

            if ($voucherCreated->shouldNotifyRequesterAdded()) {
                IdentityProductVoucherAddedNotification::send($event);
            }

            if ($voucherCreated->shouldNotifyRequesterReserved()) {
                IdentityProductVoucherReservedNotification::send($event);
            }
        } else {
            $event = $voucher->log(Voucher::EVENT_CREATED_BUDGET, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
                'employee' => $voucher->employee,
            ]);

            if ($voucher->identity && $voucher->fund->fund_formulas->count() > 0) {
                VoucherAssigned::dispatch($voucher);

                if ($voucher->fund->isTypeSubsidy()) {
                    IdentityVoucherAddedSubsidyNotification::send($event);
                } else {
                    IdentityVoucherAddedBudgetNotification::send($event);
                }
            }
        }
    }

    /**
     * @param VoucherAssigned $voucherAssigned
     * @noinspection PhpUnused
     */
    public function onVoucherAssigned(VoucherAssigned $voucherAssigned): void
    {
        $voucher = $voucherAssigned->getVoucher();
        $type = $voucher->isBudgetType() ? ($voucher->fund->isTypeBudget() ? 'budget' : 'subsidy') : 'product';

        $event = $voucher->log(Voucher::EVENT_ASSIGNED, [
            'fund' => $voucher->fund,
            'voucher' => $voucher,
            'product' => $voucher->product,
            'provider' => $voucher->product->organization ?? null,
            'sponsor' => $voucher->fund->organization,
        ], [
            'implementation_name' => $voucher->fund->fund_config->implementation->name,
        ]);

        switch ($type) {
            case 'budget': IdentityVoucherAssignedBudgetNotification::send($event); break;
            case 'subsidy': IdentityVoucherAssignedSubsidyNotification::send($event); break;
            case 'product': IdentityVoucherAssignedProductNotification::send($event); break;
        }
    }

    /**
     * @param ProductVoucherShared $voucherShared
     * @noinspection PhpUnused
     */
    public function onProductVoucherShared(ProductVoucherShared $voucherShared): void
    {
        $voucher = $voucherShared->getVoucher();

        $eventLog = $voucher->log(Voucher::EVENT_SHARED, [
            'voucher' => $voucher,
            'product' => $voucher->product,
            'provider' => $voucher->product->organization,
        ], [
            'voucher_share_message' => $voucherShared->getMessage(),
            'voucher_share_send_copy' => $voucherShared->shouldSendCopyToUser(),
        ]);

        IdentityProductVoucherSharedNotification::send($eventLog);
    }

    /**
     * @param VoucherExpireSoon $voucherExpired
     * @noinspection PhpUnused
     */
    public function onVoucherExpireSoon(VoucherExpireSoon $voucherExpired): void
    {
        $voucher = $voucherExpired->getVoucher();

        $eventRawData = [
            'link_webshop' => $voucher->fund->urlWebshop(),
            'fund_start_year' => $voucher->fund->start_date->format('Y'),
        ];

        if ($voucher->product) {
            $eventLog = $voucher->log(Voucher::EVENT_EXPIRING_SOON_PRODUCT, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'product' => $voucher->product,
                'sponsor' => $voucher->fund->organization,
            ], $eventRawData);

            IdentityVoucherExpireSoonProductNotification::send($eventLog);
        } else {
            $eventLog = $voucher->log(Voucher::EVENT_EXPIRING_SOON_BUDGET, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
            ], $eventRawData);

            IdentityVoucherExpireSoonBudgetNotification::send($eventLog);
        }
    }

    /**
     * @param VoucherExpired $voucherExpired
     * @noinspection PhpUnused
     */
    public function onVoucherExpired(VoucherExpired $voucherExpired): void
    {
        $voucher = $voucherExpired->getVoucher();

        if ($voucher->product) {
            $logEvent = $voucher->log(Voucher::EVENT_EXPIRED_PRODUCT, [
                'fund' => $voucher->fund,
                'sponsor' => $voucher->fund->organization,
                'product' => $voucher->product,
            ]);

            IdentityProductVoucherExpiredNotification::send($logEvent);
        } else {
            $logEvent = $voucher->log(Voucher::EVENT_EXPIRED_BUDGET, [
                'fund' => $voucher->fund,
                'sponsor' => $voucher->fund->organization,
            ]);

            IdentityVoucherExpiredNotification::send($logEvent);
        }
    }

    /**
     * @param VoucherDeactivated $voucherDeactivated
     * @noinspection PhpUnused
     */
    public function onVoucherDeactivated(VoucherDeactivated $voucherDeactivated): void
    {
        $employee = $voucherDeactivated->getEmployee();
        $voucher = $voucherDeactivated->getVoucher();
        $sponsor = $voucher->fund->organization;
        $fund = $voucher->fund;

        $logData = compact('fund', 'voucher', 'employee', 'sponsor');
        $logModel = $voucher->log($voucher::EVENT_DEACTIVATED, $logData, [
            'deactivation_date' => now()->format('Y-m-d'),
            'deactivation_date_locale' => format_date_locale(now()),
            'note' => $voucherDeactivated->getNote(),
            'notify_by_email' => $voucherDeactivated->shouldNotifyByEmail(),
        ]);

        IdentityVoucherDeactivatedNotification::send($logModel);
    }

    /**
     * @param VoucherSendToEmailEvent $event
     * @noinspection PhpUnused
     */
    public function onVoucherSendToEmail(VoucherSendToEmailEvent $event): void
    {
        $email = $event->getEmail();
        $voucher = $event->getVoucher();

        $eventLog = $voucher->log($voucher::EVENT_SHARED_BY_EMAIL, [
            'fund' => $voucher->fund,
            'sponsor' => $voucher->fund->organization,
        ], [
            'qr_token' => $voucher->token_without_confirmation->address,
            'voucher_product_or_fund_name' => $voucher->product->name ?? $voucher->fund->name,
        ]);

        IdentityVoucherSharedByEmailNotification::send($eventLog);

        resolve('forus.services.notification')->sendSystemMail($email, new SendVoucherMail(
            $eventLog->data,
            $voucher->fund->getEmailFrom()
        ));
    }

    /**
     * @param VoucherPhysicalCardRequestedEvent $event
     * @noinspection PhpUnused
     */
    public function onVoucherPhysicalCardRequested(VoucherPhysicalCardRequestedEvent $event): void {
        $voucher = $event->getVoucher();
        $cardRequest = $event->getCardRequest();

        $event = $voucher->log($voucher::EVENT_PHYSICAL_CARD_REQUESTED, [
            'fund'      => $voucher->fund,
            'voucher'   => $voucher,
            'sponsor'   => $voucher->fund->organization,
        ], $cardRequest->only('postcode', 'house', 'house_addition', 'city', 'address'));

        IdentityVoucherPhysicalCardRequestedNotification::send($event);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            VoucherCreated::class,
            '\App\Listeners\VoucherSubscriber@onVoucherCreated'
        );

        $events->listen(
            ProductVoucherShared::class,
            '\App\Listeners\VoucherSubscriber@onProductVoucherShared'
        );

        $events->listen(
            VoucherAssigned::class,
            '\App\Listeners\VoucherSubscriber@onVoucherAssigned'
        );

        $events->listen(
            VoucherExpireSoon::class,
            '\App\Listeners\VoucherSubscriber@onVoucherExpireSoon'
        );

        $events->listen(
            VoucherExpired::class,
            '\App\Listeners\VoucherSubscriber@onVoucherExpired'
        );

        $events->listen(
            VoucherDeactivated::class,
            '\App\Listeners\VoucherSubscriber@onVoucherDeactivated'
        );

        $events->listen(
            VoucherSendToEmailEvent::class,
            '\App\Listeners\VoucherSubscriber@onVoucherSendToEmail'
        );

        $events->listen(
            VoucherPhysicalCardRequestedEvent::class,
            '\App\Listeners\VoucherSubscriber@onVoucherPhysicalCardRequested'
        );
    }
}
