<?php

namespace App\Http\Controllers\Api\Platform\Provider\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Provider\ProviderSubsidyProductResource;
use App\Models\FundProviderProduct;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductsController
 * @package App\Http\Controllers\Api\Platform\Provider\Vouchers
 */
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param VoucherToken $voucherToken
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        IndexProductsRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('useAsProvider', $voucherToken->voucher);
        $this->authorize('viewAnyPublic', Product::class);

        $organizations = Organization::queryByIdentityPermissions($request->auth_address(), [
            'scan_vouchers'
        ])->pluck('id')->toArray();

        $organization_id = $request->input('organization_id', $organizations);
        $reservable = $request->input('reservable', false);

        if ($voucherToken->voucher->fund->isTypeSubsidy()) {
            $query = FundProviderProductQuery::whereAvailableForSubsidyVoucherFilter(
                FundProviderProduct::query(),
                $voucherToken->voucher,
                $organization_id
            );

            if ($voucherToken->voucher->product_id) {
                $query->where('product_id', $voucherToken->voucher->product_id);
            }

            if ($reservable) {
                $query->whereHas('product', function(Builder $builder) {
                    $builder->whereHas('organization', function(Builder $builder) {
                        $builder->where('reservations_subsidy_enabled', true);
                    });
                });
            }

            return ProviderSubsidyProductResource::collection($query->with(
                ProviderSubsidyProductResource::$load
            )->paginate($request->input('per_page', 10)));
        }

        $query = Product::query()->where('price', '<=', $voucherToken->voucher->amount_available);
        ProductQuery::approvedForFundsAndActiveFilter($query, $voucherToken->voucher->fund_id);

        if ($voucherToken->voucher->product_id) {
            $query->where('id', $voucherToken->voucher->product_id);
        }

        $query->whereIn('organization_id', (array) $organization_id);

        if ($reservable) {
            $query->whereHas('organization', function(Builder $builder) {
                $builder->where('reservations_budget_enabled', true);
            });
        }

        return ProductResource::collection($query->with(ProductResource::load())->paginate(
            $request->input('per_page', 10)
        ));
    }
}
