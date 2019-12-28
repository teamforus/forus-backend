<?php

namespace App\Rules;

use App\Models\Product;
use App\Models\VoucherToken;
use Illuminate\Contracts\Validation\Rule;

class ProductIdToVoucherRule implements Rule
{
    private $voucherAddress;
    private $message;

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
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $product = Product::find($value);

        /** @var VoucherToken $voucherToken */
        $voucherToken = VoucherToken::query()->where([
                'address' => $this->voucherAddress
            ])->first() ?? abort(404);

        if (!$voucherToken->voucher) {
            $this->message = trans(
                'validation.product_voucher.voucher_id_required'
            );

            return false;
        }

        $amountLeft = $voucherToken->voucher->amount - (
            $voucherToken->voucher->transactions->sum('amount')) -
            $voucherToken->voucher->product_vouchers()->sum('amount');

        if ($product->sold_out) {
            $this->message = trans(
                'validation.product_voucher.product_sold_out');
            return false;
        }

        if ($product->price > $amountLeft) {
            $this->message = trans(
                'validation.product_voucher.not_enough_voucher_funds');
            return false;
        }

        if ($product->getFundsWhereIsAvailable()->pluck('id')->search(
            $voucherToken->voucher->fund_id) === FALSE) {
            $this->message = trans(
                'validation.product_voucher.product_not_applicable_by_voucher'
            );

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
