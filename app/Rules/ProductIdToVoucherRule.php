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
     * @param string|null $voucherAddress
     * @return void
     */
    public function __construct(?string $voucherAddress)
    {
        $this->voucherAddress = $voucherAddress;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string|any  $attribute
     * @param  mixed  $product_id
     * @return bool
     */
    public function passes($attribute, $product_id): bool
    {
        $product = Product::find($product_id);
        $voucherToken = VoucherToken::whereAddress($this->voucherAddress)->first();

        // optional check for human readable output
        if (!$this->voucherAddress || !$voucherToken || (!$voucher = $voucherToken->voucher)) {
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
