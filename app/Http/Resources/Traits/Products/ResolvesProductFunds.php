<?php

namespace App\Http\Resources\Traits\Products;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Requester\ProductFundResource;
use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait ResolvesProductFunds
{
    /**
     * @param Product $product
     * @param int|null $identityId
     * @return Builder
     */
    protected function productFundsQuery(Product $product, ?int $identityId): Builder
    {
        $query = FundQuery::whereProductsAreApprovedAndActiveFilter($this->fundsQuery(), $product);

        if ($identityId !== null) {
            $query
                ->withMin([
                    'budget_vouchers as first_voucher_expire_at' => static function (Builder $query) use ($identityId) {
                        VoucherQuery::whereNotExpiredAndActive($query)
                            ->where('identity_id', $identityId)
                            ->whereDoesntHave('product_reservation');
                    },
                ], 'expire_at')
                ->withAggregate('fund_config as reservation_approve_offset', 'reservation_approve_offset')
                ->withCasts(['first_voucher_expire_at' => 'datetime']);
        }

        return $query;
    }

    /**
     * @param Product $product
     * @param Fund $fund
     * @return Carbon
     */
    protected function productFundExpireAt(Product $product, Fund $fund): Carbon
    {
        $dates = [$fund->end_date, $product->expire_at];

        if ($voucherExpireAt = $fund->getAttribute('first_voucher_expire_at')) {
            $offset = $fund->getAttribute('reservation_approve_offset');
            $dates[] = $voucherExpireAt->min($fund->end_date)->copy()->addDays($offset);
        }

        return min(array_filter($dates));
    }

    /**
     * @param Product $product
     * @param Fund $fund
     * @param FundProviderProduct|null $deal
     * @param int|null $identityId
     * @throws Exception
     * @return int|null
     */
    protected function productFundLimit(
        Product $product,
        Fund $fund,
        ?FundProviderProduct $deal,
        ?int $identityId,
    ): ?int {
        if ($identityId === null || !$fund->getAttribute('first_voucher_expire_at')) {
            return $deal?->limit_per_identity;
        }

        return ProductSubQuery::appendReservationStats([
            'identity_id' => $identityId,
            'fund_id' => $fund->id,
        ], Product::whereId($product->id))->value('limit_available');
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @return Collection<int, ProductFundResource>
     */
    private function getProductFunds(BaseFormRequest $request, Product $product): Collection
    {
        $identityId = $request->auth_id();
        $fundsQuery = $this->productFundsQuery($product, $identityId);
        $fundsQuery->with(ProductFundResource::load());

        return $fundsQuery->get()->map(function (Fund $fund) use ($product, $identityId): ProductFundResource {
            $fundProviderProduct = $product->getFundProviderProduct($fund);

            return new ProductFundResource(
                $fund,
                $product,
                $this->productFundLimit($product, $fund, $fundProviderProduct, $identityId),
                $this->productFundExpireAt($product, $fund),
                $fundProviderProduct,
                $this->productFundFeatures($product, $fund, $fundProviderProduct),
            );
        })->values();
    }
}
