<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Resources\Provider\ProviderSubsidyProductResource;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
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

        $voucher = $voucherToken->voucher;

        /** @var Builder $query */
        $query = FundProviderProduct::whereHas('product', static function(
            Builder $query
        ) use ($request, $voucher) {
            $query->where(static function(Builder $builder) use ($voucher, $request) {
                $providersQuery = FundProviderQuery::whereApprovedForFundsFilter(
                    FundProvider::query(), $voucher->fund_id,'subsidy', $voucher->product_id
                );

                if ($request->has('organization_id')) {
                    $providersQuery->where('organization_id', $request->get('organization_id'));
                }

                $builder->whereIn('organization_id', $providersQuery->pluck('organization_id'));
            });

            return ProductQuery::approvedForFundsAndActiveFilter($query, $voucher->fund->id);
        });

        $query = FundProviderProductQuery::whereInLimitsFilter($query, $voucher->identity_address);

        return ProviderSubsidyProductResource::collection($query->with(
            ProviderSubsidyProductResource::$load
        )->paginate($request->input('per_page', 10)));
    }
}
