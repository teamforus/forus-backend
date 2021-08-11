<?php

namespace App\Listeners;

use App\Events\Products\ProductReserved;
use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Events\Vouchers\VoucherDeactivated;
use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpiring;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Notifications\Identities\Voucher\IdentityProductVoucherAddedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherReservedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherSharedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAddedBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAddedSubsidyNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedSubsidyNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherDeactivatedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonProductNotification;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use App\Services\TokenGeneratorService\TokenGenerator;
use Illuminate\Events\Dispatcher;

/**
 * Class VoucherSubscriber
 * @property IRecordRepo $recordService
 * @property NotificationService $mailService
 * @property TokenGenerator $tokenGenerator
 * @package App\Listeners
 */
class VoucherSubscriber
{
    protected $mailService;
    protected $recordService;
    protected $tokenGenerator;

    /**
     * VoucherSubscriber constructor.
     */
    public function __construct()
    {
        $this->mailService = resolve('forus.services.notification');
        $this->recordService = resolve('forus.services.record');
        $this->tokenGenerator = resolve('token_generator');
    }

    /**
     * @param VoucherCreated $voucherCreated
     * @noinspection PhpUnused
     */
    public function onVoucherCreated(VoucherCreated $voucherCreated): void
    {
        $voucher = $voucherCreated->getVoucher();
        $product = $voucher->product;

        $voucher->tokens()->create([
            'address'           => $this->tokenGenerator->address(),
            'need_confirmation' => true,
        ]);

        /** @var VoucherToken $voucherToken */
        $voucher->tokens()->create([
            'address'           => $this->tokenGenerator->address(),
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
            ], $voucher->only('note'));

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
            ], $voucher->only('note'));

            if ($voucher->identity_address && $voucher->fund->fund_formulas->count() > 0) {
                $voucher->assignedVoucherEmail(record_repo()->primaryEmailByAddress(
                    $voucher->identity_address
                ));

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
        $product = $voucher->product;

        $event = $voucher->log(Voucher::EVENT_ASSIGNED, [
            'fund' => $voucher->fund,
            'voucher' => $voucher,
            'sponsor' => $voucher->fund->organization,
        ]);

        if ($voucher->fund->isTypeSubsidy()) {
            IdentityVoucherAssignedSubsidyNotification::send($event);
        } else {
            IdentityVoucherAssignedBudgetNotification::send($event);
        }

        $transData = [
            "implementation_name" => Implementation::active()->name,
            "fund_name" => $voucher->fund->name
        ];

        if ($product) {
            $this->mailService->sendPushNotification(
                $voucher->identity_address,
                trans('push.voucher.bought.title', $transData),
                trans('push.voucher.bought.body', $transData),
                'voucher.assigned'
            );
        } else {
            $this->mailService->sendPushNotification(
                $voucher->identity_address,
                trans('push.voucher.activated.title', $transData),
                trans('push.voucher.activated.body', $transData),
                'voucher.assigned'
            );
        }

        $voucher->assignedVoucherEmail(record_repo()->primaryEmailByAddress(
            $voucher->identity_address
        ));
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
            'voucher_share_send_copy' => $voucherShared->isSendCopyToUser(),
        ]);

        IdentityProductVoucherSharedNotification::send($eventLog);
    }

    /**
     * @param VoucherExpiring $voucherExpired
     * @noinspection PhpUnused
     */
    public function onVoucherExpiring(VoucherExpiring $voucherExpired): void
    {
        $voucher = $voucherExpired->getVoucher();

        if ($voucher->product) {
            $eventLog = $voucher->log(Voucher::EVENT_EXPIRING_SOON_PRODUCT, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
                'product' => $voucher->product,
            ]);

            IdentityVoucherExpireSoonProductNotification::send($eventLog);
        } else {
            $eventLog = $voucher->log(Voucher::EVENT_EXPIRING_SOON_BUDGET, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
            ]);

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
     * @param VoucherDeactivated $voucherExpired
     * @noinspection PhpUnused
     */
    public function onVoucherDeactivated(VoucherDeactivated $voucherExpired): void
    {
        $employee = $voucherExpired->getEmployee();
        $voucher = $voucherExpired->getVoucher();
        $sponsor = $voucher->fund->organization;
        $fund = $voucher->fund;

        $logData = compact('fund', 'voucher', 'employee', 'sponsor');
        $logModel = $voucher->log($voucher::EVENT_DEACTIVATED, $logData, [
            'note' => $voucherExpired->getNote(),
            'notify_by_email' => $voucherExpired->shouldNotifyByEmail(),
        ]);

        IdentityVoucherDeactivatedNotification::send($logModel);
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
            VoucherExpiring::class,
            '\App\Listeners\VoucherSubscriber@onVoucherExpiring'
        );

        $events->listen(
            VoucherExpired::class,
            '\App\Listeners\VoucherSubscriber@onVoucherExpired'
        );

        $events->listen(
            VoucherDeactivated::class,
            '\App\Listeners\VoucherSubscriber@onVoucherDeactivated'
        );
    }
}
