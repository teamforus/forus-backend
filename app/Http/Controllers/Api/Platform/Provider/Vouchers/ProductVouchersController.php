<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers\IndexProductVouchersRequest;
use App\Http\Resources\Provider\App\ProviderVoucherResource;
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

        $productVouchersQuery = VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucherToken->voucher->product_vouchers()->getQuery(),
            $request->auth_address(),
            $voucherToken->voucher->fund_id,
            $request->input('organization_id')
        );

        return ProviderVoucherResource::queryCollection($productVouchersQuery, $request);
    }
}
