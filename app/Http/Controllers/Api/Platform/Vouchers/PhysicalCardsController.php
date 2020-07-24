<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhysicalCardResource;
use App\Models\VoucherToken;
use App\Http\Requests\Api\Platform\Vouchers\StorePhysicalCardRequest;
use App\Http\Requests\Api\Platform\Vouchers\RequestPhysicalCardRequest;

class PhysicalCardsController extends Controller
{
    /**
     * Link existing physical card to existing voucher
     * @param StorePhysicalCardRequest $request
     * @param VoucherToken $voucherToken
     * @return PhysicalCardResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StorePhysicalCardRequest $request,
        VoucherToken $voucherToken
    ): PhysicalCardResource {
        $this->authorize('storePhysicalCard', $voucherToken->voucher);

        return new PhysicalCardResource($voucherToken->voucher->physical_cards()->create(
            $request->only('code')
        ));
    }

    /**
     * Request a physical card
     *
     * @param RequestPhysicalCardRequest $request
     * @param VoucherToken $voucherToken
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function request(
        RequestPhysicalCardRequest $request,
        VoucherToken $voucherToken
    ): \Illuminate\Http\JsonResponse {
        $this->authorize('requestPhysicalCard', $voucherToken->voucher);

        // todo: Target email is unknown yet
        /*resolve('forus.services.notification')->requestPhysicalCard(
            '',
            $voucherToken->voucher->fund->getEmailFrom(),
            $request->input('post_code'),
            $request->input('house_number')
        );*/

        return response()->json([
            'success' => true
        ], 200);
    }
}
