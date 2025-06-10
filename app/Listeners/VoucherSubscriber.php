<?php

namespace App\Listeners;

use App\Events\Products\ProductReserved;
use App\Events\Products\ProductReservedBySponsor;
use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Events\Vouchers\VoucherDeactivated;
use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpireSoon;
use App\Events\Vouchers\VoucherLimitUpdated;
use App\Events\Vouchers\VoucherPhysicalCardRequestedEvent;
use App\Events\Vouchers\VoucherSendToEmailEvent;
use App\Mail\Vouchers\SendVoucherMail;
use App\Models\Voucher;
use App\Models\VoucherToken;
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
use App\Notifications\Identities\Voucher\IdentityVoucherPhysicalCardRequestedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherSharedByEmailNotification;
use App\Notifications\Organizations\PhysicalCardRequest\PhysicalCardRequestCreatedSponsorNotification;
use Illuminate\Events\Dispatcher;

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
            'address' => resolve('token_generator')->address(),
            'need_confirmation' => true,
        ]);

        /** @var VoucherToken $voucherToken */
        $voucher->tokens()->create([
            'address' => resolve('token_generator')->address(),
            'need_confirmation' => false,
        ]);

        if ($product) {
            $product->updateSoldOutState();

            $event = $voucher->log(Voucher::EVENT_CREATED_PRODUCT, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'product' => $product,
                'provider' => $product->organization,
                'sponsor' => $voucher->fund->organization,
                'employee' => $voucher->employee,
                'implementation' => $voucher->fund->getImplementation(),
            ], $voucher->only('note'));

            if ($voucherCreated->shouldNotifyProviderReserved()) {
                ProductReserved::dispatch($product, $voucher);
            }

            if ($voucherCreated->shouldNotifyProviderReservedBySponsor()) {
                ProductReservedBySponsor::dispatch($product, $voucher);
            }

            if ($voucher->identity) {
                VoucherAssigned::dispatch($voucher, !$voucher->product_reservation_id);
            }

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
                'implementation' => $voucher->fund->getImplementation(),
            ], $voucher->only('note'));

            if ($voucher->identity && $voucher->fund->fund_formulas->count() > 0) {
                VoucherAssigned::dispatch($voucher);

                if (!$voucher->isTypePayout()) {
                    if ($voucher->fund->isTypeSubsidy()) {
                        IdentityVoucherAddedSubsidyNotification::send($event);
                    } else {
                        IdentityVoucherAddedBudgetNotification::send($event);
                    }
                }
            }
        }

        if (!$voucher->isTypePayout()) {
            $voucher->reportBackofficeReceived();
        }
    }

    /**
     * @param VoucherLimitUpdated $event
     * @noinspection PhpUnused
     */
    public function onVoucherLimitUpdated(VoucherLimitUpdated $event): void
    {
        $voucher = $event->getVoucher();

        $voucher->log(Voucher::EVENT_LIMIT_MULTIPLIER_CHANGED, [
            'fund' => $voucher->fund,
            'voucher' => $voucher,
            'sponsor' => $voucher->fund->organization,
            'employee' => $voucher->employee,
            'implementation' => $voucher->fund->getImplementation(),
        ], [
            'voucher_limit_multiplier_old' => $event->getOldLimitMultiplier(),
        ]);
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
            'implementation' => $voucher->fund->getImplementation(),
        ], [
            'implementation_name' => $voucher->fund->fund_config->implementation->name,
        ]);

        if ($voucherAssigned->shouldNotifyRequesterAssigned() && !$voucher->isTypePayout()) {
            switch ($type) {
                case 'budget': IdentityVoucherAssignedBudgetNotification::send($event);
                    break;
                case 'subsidy': IdentityVoucherAssignedSubsidyNotification::send($event);
                    break;
                case 'product': IdentityVoucherAssignedProductNotification::send($event);
                    break;
            }
        }

        if (!$voucher->isTypePayout()) {
            $voucher->reportBackofficeReceived();
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
            'implementation' => $voucher->fund->getImplementation(),
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
                'implementation' => $voucher->fund->getImplementation(),
            ], $eventRawData);

            IdentityVoucherExpireSoonProductNotification::send($eventLog);
        } else {
            $eventLog = $voucher->log(Voucher::EVENT_EXPIRING_SOON_BUDGET, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
                'implementation' => $voucher->fund->getImplementation(),
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
                'implementation' => $voucher->fund->getImplementation(),
            ]);

            IdentityProductVoucherExpiredNotification::send($logEvent);
        } else {
            $logEvent = $voucher->log(Voucher::EVENT_EXPIRED_BUDGET, [
                'fund' => $voucher->fund,
                'sponsor' => $voucher->fund->organization,
                'implementation' => $voucher->fund->getImplementation(),
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
        $implementation = $voucherDeactivated->getVoucher()->fund->getImplementation();
        $employee = $voucherDeactivated->getEmployee();
        $voucher = $voucherDeactivated->getVoucher();
        $sponsor = $voucher->fund->organization;
        $fund = $voucher->fund;

        $logData = compact('fund', 'voucher', 'employee', 'sponsor', 'implementation');

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
            'implementation' => $voucher->fund->getImplementation(),
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
    public function onVoucherPhysicalCardRequested(VoucherPhysicalCardRequestedEvent $event): void
    {
        $physicalCardRequest = $event->getCardRequest();

        $address = $physicalCardRequest->address . ' ' . implode(', ', array_filter([
            $physicalCardRequest->house,
            $physicalCardRequest->house_addition,
            $physicalCardRequest->postcode,
            $physicalCardRequest->city,
        ]));

        $eventLog = $physicalCardRequest->voucher->log(Voucher::EVENT_PHYSICAL_CARD_REQUESTED, [
            'fund' => $physicalCardRequest->voucher->fund,
            'sponsor' => $physicalCardRequest->voucher->fund->organization,
            'voucher' => $physicalCardRequest->voucher,
            'employee' => $physicalCardRequest->employee,
            'implementation' => $physicalCardRequest->voucher->fund->getImplementation(),
            'physical_card_request' => $physicalCardRequest,
        ], [
            'note' => "Adresgegevens: $address",
            'address' => $address,
        ]);

        PhysicalCardRequestCreatedSponsorNotification::send($eventLog);

        if ($event->shouldNotifyRequester()) {
            IdentityVoucherPhysicalCardRequestedNotification::send($eventLog);
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

        $events->listen(VoucherCreated::class, "$class@onVoucherCreated");
        $events->listen(VoucherLimitUpdated::class, "$class@onVoucherLimitUpdated");
        $events->listen(ProductVoucherShared::class, "$class@onProductVoucherShared");
        $events->listen(VoucherAssigned::class, "$class@onVoucherAssigned");
        $events->listen(VoucherExpireSoon::class, "$class@onVoucherExpireSoon");
        $events->listen(VoucherExpired::class, "$class@onVoucherExpired");
        $events->listen(VoucherDeactivated::class, "$class@onVoucherDeactivated");
        $events->listen(VoucherSendToEmailEvent::class, "$class@onVoucherSendToEmail");
        $events->listen(VoucherPhysicalCardRequestedEvent::class, "$class@onVoucherPhysicalCardRequested");
    }
}
