<?php

namespace App\Listeners;

use App\Events\Products\ProductReserved;
use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpiring;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Notifications\Identities\Voucher\IdentityProductVoucherAddedNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityProductVoucherSharedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAddedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherAssignedNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpiredNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonBudgetNotification;
use App\Notifications\Identities\Voucher\IdentityVoucherExpireSoonProductNotification;
use App\Services\Forus\Notification\NotificationService;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Events\Dispatcher;

/**
 * Class VoucherSubscriber
 * @property IRecordRepo $recordService
 * @property NotificationService $mailService
 * @package App\Listeners
 */
class VoucherSubscriber
{
    private $mailService;
    private $recordService;
    private $tokenGenerator;

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
     */
    public function onVoucherCreated(
        VoucherCreated $voucherCreated
    ): void {
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
            ]);

            IdentityProductVoucherAddedNotification::send($event);
        } else if ($voucher->identity_address) {
            $voucher->assignedVoucherEmail(record_repo()->primaryEmailByAddress(
                $voucher->identity_address
            ));

            $event = $voucher->log(Voucher::EVENT_CREATED_BUDGET, [
                'fund' => $voucher->fund,
                'voucher' => $voucher,
                'sponsor' => $voucher->fund->organization,
            ]);

            IdentityVoucherAddedNotification::send($event);
        }
    }

    /**
     * @param VoucherAssigned $voucherCreated
     */
    public function onVoucherAssigned(
        VoucherAssigned $voucherCreated
    ) :void {
        $voucher = $voucherCreated->getVoucher();
        $product = $voucher->product;
        $implementation = Implementation::activeModel();

        $eventLog = $voucher->log(Voucher::EVENT_ASSIGNED, [
            'fund' => $voucher->fund,
            'voucher' => $voucher,
            'sponsor' => $voucher->fund->organization,
        ]);

        IdentityVoucherAssignedNotification::send($eventLog);

        $transData = [
            "implementation_name" => $implementation->name ?? 'General',
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
     */
    public function onProductVoucherShared(
        ProductVoucherShared $voucherShared
    ): void {
        $voucher = $voucherShared->getVoucher();
        $message = $voucherShared->getMessage();

        $eventLog = $voucher->log(Voucher::EVENT_SHARED, [
            'voucher' => $voucher,
            'product' => $voucher->product,
            'provider' => $voucher->product->organization,
        ], [
            'voucher_share_message' => $message
        ]);

        IdentityProductVoucherSharedNotification::send($eventLog);
    }

    /**
     * @param VoucherExpiring $voucherExpired
     */
    public function onVoucherExpiring(
        VoucherExpiring $voucherExpired
    ): void {
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
     */
    public function onVoucherExpired(
        VoucherExpired $voucherExpired
    ): void {
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
    }
}
