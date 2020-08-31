<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\FundProvider;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param VoucherToken $voucherToken
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('useAsProvider', $voucherToken->voucher);
        $this->authorize('viewAnyPublic', Product::class);

        $voucher = $voucherToken->voucher;

        $productsQuery = Product::where(static function(Builder $builder) use (
            $voucher, $request
        ) {
            $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $voucher->fund_id,
                'subsidy',
                $voucher->product_id
            );

            if ($request->has('organization_id')) {
                $providersQuery->where('organization_id', $request->get('organization_id'));
            }

            $builder->whereIn('organization_id', $providersQuery->pluck('organization_id'));
        });

        return ProductResource::collection(ProductQuery::approvedForFundsAndActiveFilter(
            $productsQuery, $voucher->fund->id
        )->with(ProductResource::$load)->paginate());
    }
}
