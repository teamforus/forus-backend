<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers\IndexProductVouchersRequest;
use App\Http\Resources\Provider\ProviderVoucherResource;
use App\Models\VoucherToken;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductVouchersController
 * @package App\Http\Controllers\Api\Platform\Provider\Vouchers
 */
class ProductVouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductVouchersRequest $request
     * @param VoucherToken $voucherToken
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProductVouchersRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        $product_vouchers = VoucherQuery::whereProductVouchersCanBeScannedForFundBy(
            $voucherToken->voucher->product_vouchers()->getQuery(),
            $request->auth_address(),
            $voucherToken->voucher->fund_id
        )->whereDoesntHave('transactions');

        return ProviderVoucherResource::collection($product_vouchers->paginate(
            $request->input('per_page', 10)
        ));
    }
}
