<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Models\VoucherToken;
use App\Http\Controllers\Controller;

/**
 * Class VouchersController
 * @package App\Http\Controllers\Api\Platform\Provider
 */
class VouchersController extends Controller
{
    /**
     * @param VoucherToken $voucherToken
     * @return ProviderVoucherResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(VoucherToken $voucherToken): ProviderVoucherResource
    {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        return new ProviderVoucherResource($voucherToken);
    }
}
