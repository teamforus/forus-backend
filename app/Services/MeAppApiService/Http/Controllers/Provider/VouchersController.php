<?php

namespace App\Services\MeAppApiService\Http\Controllers\Provider;

use App\Http\Controllers\Api\Platform\Provider\VouchersController as LegacyVouchersController;
use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Models\VoucherToken;
use App\Services\MeAppApiService\Http\Resources\Provider\ProviderVoucherTokenResource;
use Illuminate\Auth\Access\AuthorizationException;

class VouchersController extends Controller
{
    /**
     * Get a scanned voucher.
     *
     * @param BaseFormRequest $request
     * @param VoucherToken $voucherToken
     * @throws AuthorizationException
     * @return ProviderVoucherTokenResource
     */
    public function show(BaseFormRequest $request, VoucherToken $voucherToken): ProviderVoucherTokenResource
    {
        $response = resolve(LegacyVouchersController::class)->show($request, $voucherToken);

        return new ProviderVoucherTokenResource($response->resource);
    }
}
