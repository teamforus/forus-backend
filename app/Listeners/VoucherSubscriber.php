<?php

namespace App\Listeners;

use App\Events\Vouchers\VoucherCreated;
use App\Models\Implementation;
use Illuminate\Events\Dispatcher;

class VoucherSubscriber
{
    private $mailService;

    /**
     * VoucherSubscriber constructor.
     */
    public function __construct()
    {
        $this->mailService = resolve('forus.services.mail_notification');
    }

    /**
     * @param VoucherCreated $voucherCreated
     */
    public function onVoucherCreated(VoucherCreated $voucherCreated) {
        $voucher = $voucherCreated->getVoucher();

        $voucherTokens = [];

        $voucherTokens[] = $voucher->tokens()->create([
            'address'           => app()->make('token_generator')->address(),
            'need_confirmation' => true,
        ]);

        $voucherTokens[] = $voucher->tokens()->create([
            'address'           => app()->make('token_generator')->address(),
            'need_confirmation' => false,
        ]);

        if ($product = $voucher->product) {
            $product->updateSoldOutState();

            if ($product->sold_out) {
                $this->mailService->productSoldOut(
                    $product->organization->emailServiceId(),
                    $product->name,
                    Implementation::active()['url_provider']
                );
            }

            $this->mailService->productReserved(
                $product->organization->emailServiceId(),
                $product->name,
                format_date_locale($product->expire_at)
            );

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

        $voucher->sendToEmail();
    }

    /**
     * Get storage
     * @return \Storage
     */
    private function storage() {
        return app()->make('filesystem')->disk(
            env('VOUCHER_QR_STORAGE_DRIVER', 'public')
        );
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
    }
}
