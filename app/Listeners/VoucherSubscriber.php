<?php

namespace App\Listeners;

use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Models\Implementation;
use App\Models\VoucherToken;
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
    public function onVoucherCreated(VoucherCreated $voucherCreated) {
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

            if ($product->sold_out) {
                $product->sendSoldOutEmail();
            }

            $product->sendProductReservedEmail($voucher);
            $product->sendProductReservedUserEmail($voucher);
        } else if ($voucher->identity_address) {
            $voucher->assignedVoucherEmail(record_repo()->primaryEmailByAddress(
                $voucher->identity_address
            ));
        }
    }

    /**
     * @param VoucherAssigned $voucherCreated
     */
    public function onVoucherAssigned(VoucherAssigned $voucherCreated) {
        $voucher = $voucherCreated->getVoucher();
        $product = $voucher->product;
        $implementation = Implementation::activeModel();

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
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            VoucherCreated::class,
            '\App\Listeners\VoucherSubscriber@onVoucherCreated'
        );

        $events->listen(
            VoucherAssigned::class,
            '\App\Listeners\VoucherSubscriber@onVoucherAssigned'
        );
    }
}
