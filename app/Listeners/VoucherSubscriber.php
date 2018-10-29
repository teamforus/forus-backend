<?php

namespace App\Listeners;

use App\Events\Vouchers\VoucherCreated;
use App\Models\VoucherToken;
use Illuminate\Events\Dispatcher;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VoucherSubscriber
{
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
