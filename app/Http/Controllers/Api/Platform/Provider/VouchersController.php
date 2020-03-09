<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Models\VoucherToken;
use App\Http\Controllers\Controller;

class VouchersController extends Controller
{
    /**
     * @param VoucherToken $voucherToken
     * @return ProviderVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        VoucherToken $voucherToken
    ) {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        return new ProviderVoucherResource($voucherToken);
    }
}
