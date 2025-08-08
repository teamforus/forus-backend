<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;

class ProductIdInStockRule extends BaseRule
{
    private Fund $fund;
    private ?array $otherReservations;

    /**
     * Create a new rule instance.
     *
     * @param Fund $fund
     * @param array|null $otherReservations
     */
    public function __construct(Fund $fund, array $otherReservations = null)
    {
        $this->fund = $fund;
        $this->otherReservations = $otherReservations;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $product_id = $value;
        $product = Product::find($product_id);

        if (!$product) {
            return $this->reject(__('validation.product_voucher.product_not_found'));
        }

        if ($product->isInformational()) {
            return $this->reject(__('validation.product_voucher.product_is_informational'));
        }

        if ($product->sold_out) {
            return $this->reject(__('validation.product_voucher.product_sold_out'));
        }

        if ($product->sponsor_organization_id &&
            ($product->sponsor_organization_id !== $this->fund->organization_id)) {
            return $this->reject(__('validation.product_voucher.product_price_type_not_regular'));
        }

        if (!$product->unlimited_stock &&
            $this->otherReservations &&
            $product->stock_amount < $this->otherReservations[$product_id]) {
            return $this->reject(__('validation.in'));
        }

        if (ProductQuery::approvedForFundsAndActiveFilter(Product::query(), $this->fund->id)->where([
            'id' => $product->id,
        ])->doesntExist()) {
            return $this->reject(__('validation.product_not_approved'));
        }

        return true;
    }
}
