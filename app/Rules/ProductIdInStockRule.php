<?php

namespace App\Rules;

use App\Models\Fund;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;

class ProductIdInStockRule extends BaseRule
{
    protected $messageTransPrefix = 'validation.product_voucher.';
    private $fund;
    private $otherReservations;

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
     * @param  mixed  $product_id
     * @return bool
     */
    public function passes($attribute, $product_id): bool
    {
        $product = Product::find($product_id);

        if (!$product) {
            return $this->rejectTrans('product_not_found');
        }

        if ($product->sold_out) {
            return $this->rejectTrans('product_sold_out');
        }

        if (!$product->unlimited_stock &&
            $this->otherReservations &&
            $product->stock_amount < $this->otherReservations[$product_id]) {
            return $this->rejectTrans('not_enough_stock', [
                'product_name' => $product->name
            ]);
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(), $this->fund->id
        )->where('id', $product->id)->exists();
    }
}
