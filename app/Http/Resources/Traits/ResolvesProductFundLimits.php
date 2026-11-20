<?php

namespace App\Http\Resources\Traits;

use App\Models\Fund;
use App\Models\FundProviderProduct;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use Exception;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesProductFundLimits
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
            $query->withExists([
                'budget_vouchers as has_applicable_voucher' => static function (Builder $query) use ($identityId) {
                    VoucherQuery::whereNotExpiredAndActive($query)
                        ->where('identity_id', $identityId)
                        ->whereDoesntHave('product_reservation');
                },
            ]);
        }

        return $query;
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
        if ($identityId === null || !$fund->getAttribute('has_applicable_voucher')) {
            return $deal?->limit_per_identity;
        }

        return ProductSubQuery::appendReservationStats([
            'identity_id' => $identityId,
            'fund_id' => $fund->id,
        ], Product::whereId($product->id))->value('limit_available');
    }
}
