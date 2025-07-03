<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers\IndexProductVouchersRequest;
use App\Http\Resources\Provider\App\ProviderAppVoucherResource;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductVouchersController extends Controller
{
    /**
     * @param IndexProductVouchersRequest $request
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProductVouchersRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', Product::class);
        $this->authorize('useChildVoucherAsProvider', $voucherToken->voucher);

        $productVouchersQuery = VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            builder: $voucherToken->voucher->product_vouchers()->getQuery(),
            identity_address: $request->auth_address(),
            fund_id: $voucherToken->voucher->fund_id,
            organization_id: $request->get('organization_id'),
        );

        return ProviderAppVoucherResource::queryCollection($productVouchersQuery, $request);
    }
}
