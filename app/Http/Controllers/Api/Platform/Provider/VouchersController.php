<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Provider\App\ProviderVoucherProxyResource;
use App\Http\Resources\Provider\App\ProviderVoucherResource;
use App\Models\VoucherToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

/**
 * Class VouchersController
 * @package App\Http\Controllers\Api\Platform\Provider
 */
class VouchersController extends Controller
{
    /**
     * @param BaseFormRequest $request
     * @param VoucherToken $voucherToken
     * @return ProviderVoucherResource|\Illuminate\Auth\Access\Response
     * @throws AuthorizationException
     */
    public function show(BaseFormRequest $request, VoucherToken $voucherToken)
    {
        $useAsProvider = Gate::inspect('useAsProvider', $voucherToken->voucher);
        $useChildVouchers = Gate::allows('useChildVoucherAsProvider', $voucherToken->voucher);
        $sellProductsToVouchers = Gate::allows('viewRegularVoucherAvailableProductsAsProvider', $voucherToken->voucher);

        $isMobileClient = $request->isMeApp();
        $clientVersion = $request->client_version();
        $isUpdatedClient = $isMobileClient && $clientVersion && $clientVersion >= 1;

        if ($useAsProvider->allowed() || ($isUpdatedClient && ($useChildVouchers || $sellProductsToVouchers))) {
            return new ProviderVoucherResource($voucherToken);
        }

        return $useAsProvider->authorize();
    }
}
