<?php

namespace App\Rules;

use App\Models\Product;
use App\Models\VoucherToken;
use App\Scopes\Builders\ProductQuery;

class ProductIdToVoucherRule extends BaseRule
{
    protected $messageTransPrefix = 'validation.product_voucher.';
    private $voucherAddress;

    /**
     * Create a new rule instance.
     *
     * @param string $voucherAddress
     * @return void
     */
    public function __construct($voucherAddress)
    {
        $this->voucherAddress = $voucherAddress;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $product_id
     * @return bool
     */
    public function passes($attribute, $product_id)
    {
        $product = Product::find($product_id);
        $voucherToken = VoucherToken::whereAddress($this->voucherAddress)->first();

        // optional check for human readable output
        if (!$voucher = $voucherToken->voucher) {
            return $this->rejectTrans('voucher_id_required');
        }

        if ($product->sold_out) {
            return $this->rejectTrans('product_sold_out');
        }

        if ($product->price > $voucher->amount_available) {
            return $this->rejectTrans('not_enough_voucher_funds');
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(),
            $voucher->fund_id
        )->where('id', $product->id)->exists();
    }
}
