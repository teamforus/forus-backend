<?php

namespace App\Listeners;

use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Models\Implementation;
use Illuminate\Events\Dispatcher;

class VoucherSubscriber
{
    private $mailService;
    private $tokenGenerator;

    /**
     * VoucherSubscriber constructor.
     */
    public function __construct()
    {
        $this->mailService = resolve('forus.services.mail_notification');
        $this->tokenGenerator = resolve('token_generator');
    }

    /**
     * @param VoucherCreated $voucherCreated
     */
    public function onVoucherCreated(VoucherCreated $voucherCreated) {
        $voucher = $voucherCreated->getVoucher();

        $voucher->tokens()->create([
            'address'           => $this->tokenGenerator->address(),
            'need_confirmation' => true,
        ]);

        $voucher->tokens()->create([
            'address'           => $this->tokenGenerator->address(),
            'need_confirmation' => false,
        ]);

        if ($product = $voucher->product) {
            $product->updateSoldOutState();

            if ($product->sold_out) {
                $this->mailService->productSoldOut(
                    $product->organization->email,
                    $product->organization->emailServiceId(),
                    $product->name,
                    Implementation::active()['url_provider']
                );
            }

            $this->mailService->productReserved(
                $product->organization->email,
                $product->organization->emailServiceId(),
                $product->name,
                format_date_locale($product->expire_at)
            );
        }

        if ($voucher->identity_address) {
            VoucherAssigned::dispatch($voucher);
        }
    }

    /**
     * @param VoucherAssigned $voucherCreated
     */
    public function onVoucherAssigned(VoucherAssigned $voucherCreated) {
        $voucher = $voucherCreated->getVoucher();

        try {
            $voucher->fund->getWallet()->makeTransaction(
                $voucher->getWallet()->address,
                $voucher->amount
            );
        } catch (\Exception $exception) {
            logger()->debug($exception->getMessage());
        }

        if ($product = $voucher->product) {
            $imp = Implementation::query()->where([
                'key' => Implementation::activeKey('general')
            ])->first();

            $transData = [
                "implementation_name" => $imp ? $imp->name : 'General'
            ];

            $this->mailService->sendPushNotification(
                $voucher->identity_address,
                trans('push.voucher.bought.title', $transData),
                trans('push.voucher.bought.body', $transData)
            );
        } else {
            $transData = [
                "fund_name" => $voucher->fund->name
            ];

            $this->mailService->sendPushNotification(
                $voucher->identity_address,
                trans('push.voucher.activated.title', $transData),
                trans('push.voucher.activated.body', $transData)
            );
        }

        $email = resolve('forus.services.record')->primaryEmailByAddress(
            $voucher->identity_address
        );

        $voucher->sendToEmail($email);
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
