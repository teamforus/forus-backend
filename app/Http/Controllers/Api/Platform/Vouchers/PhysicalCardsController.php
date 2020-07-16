<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoucherResource;
use App\Models\VoucherToken;
use App\Http\Requests\Api\Platform\Vouchers\StorePhysicalCardRequest;
use App\Http\Requests\Api\Platform\Vouchers\RequestPhysicalCardRequest;

class PhysicalCardsController extends Controller
{
    /**
     * @param StorePhysicalCardRequest $request
     * @param VoucherToken $voucherToken
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storePhysicalCard(
        StorePhysicalCardRequest $request,
        VoucherToken $voucherToken
    ) {
        $this->authorize('storePhysicalCard', $voucherToken->voucher);

        $voucherToken->voucher->physical_cards()->create([
            'physical_card_code' => $request->input('code')
        ]);

        return new VoucherResource(
            $voucherToken->voucher->load(VoucherResource::$load)
        );
    }

    /**
     * @param RequestPhysicalCardRequest $request
     * @param VoucherToken $voucherToken
     * @return VoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function requestPhysicalCard(
        RequestPhysicalCardRequest $request,
        VoucherToken $voucherToken
    ) {
        $this->authorize('requestPhysicalCard', $voucherToken->voucher);

        $notificationService = resolve('forus.services.notification');
        //* Temp fix - should be sent to a specific email address? *//
        $email = $voucherToken->voucher->assignedVoucherEmail();
        $implementation = $voucherToken->voucher->fund->fund_config->implementation;

        $notificationService->requestPhysicalCard(
            $email,
            $implementation->getEmailFrom(),
            $request->input('post_code'),
            $request->input('house_number')
        );

        return new VoucherResource(
            $voucherToken->voucher->load(VoucherResource::$load)
        );
    }
}
