<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Resources\Provider\ProviderSubsidyProductResource;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderProductQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param VoucherToken $voucherToken
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProductsRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('useAsProvider', $voucherToken->voucher);
        $this->authorize('viewAnyPublic', Product::class);

        $query = FundProviderProductQuery::whereAvailableForVoucherFilter(
            FundProviderProduct::query(), $voucherToken->voucher
        );

        return ProviderSubsidyProductResource::collection($query->with(
            ProviderSubsidyProductResource::$load
        )->paginate($request->input('per_page', 10)));
    }
}
