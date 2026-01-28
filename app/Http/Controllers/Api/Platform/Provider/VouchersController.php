<?php

namespace App\Http\Controllers\Api\Platform\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Provider\App\ProviderAppVoucherTokenResource;
use App\Models\VoucherToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class VouchersController extends Controller
{
    /**
     * @param BaseFormRequest $request
     * @param VoucherToken $voucherToken
     *@throws AuthorizationException
     * @return ProviderAppVoucherTokenResource|\Illuminate\Auth\Access\Response
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
            return ProviderAppVoucherTokenResource::create($voucherToken);
        }

        return $useAsProvider->authorize();
    }
}
