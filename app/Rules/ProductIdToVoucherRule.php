<?php

namespace App\Rules;

use App\Models\Product;
use App\Models\Voucher;
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
        /**
         * @var Product $product
         * @var Voucher $voucher
         */
        $product = Product::query()->find($value);

        /** @var VoucherToken $voucherToken */
        $voucherToken = VoucherToken::getModel()->where([
                'address' => $this->voucherAddress
            ])->first() ?? abort(404);

        $voucher = $voucherToken->voucher ?? abort(404);

        if (!$voucher) {
            $this->message = trans(
                'validation.product_voucher.voucher_id_required'
            );

            return false;
        }

        $amountLeft = $voucher->amount - (
            $voucher->transactions->sum('amount')) -
            $voucher->product_vouchers()->sum('amount');

        if ($product->price > $amountLeft) {
            $this->message = trans(
                'validation.product_voucher.not_enough_voucher_funds'
            );

            return false;
        }

        $suppliedFundIds = $product->organization->supplied_funds_approved;

        $funds = $product->product_category->funds()->whereIn(
            'funds.id', $suppliedFundIds->pluck('id')
        )->pluck('funds.id');

        if ($funds->search($voucher->fund_id) === false) {
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
