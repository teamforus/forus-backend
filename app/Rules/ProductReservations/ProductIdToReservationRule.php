<?php

namespace App\Rules\ProductReservations;

use App\Models\Product;
use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;

/**
 * Class ProductIdToReservationRule
 * @package App\Rules
 */
class ProductIdToReservationRule extends BaseRule
{
    protected $messageTransPrefix = 'validation.product_reservation.';
    private $voucherAddress;
    private $priceType;

    /**
     * Create a new rule instance.
     *
     * @param string|null $voucherAddress
     * @param string|null $priceType
     */
    public function __construct(?string $voucherAddress, string $priceType = null)
    {
        $this->priceType = $priceType;
        $this->voucherAddress = $voucherAddress;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string|any $attribute
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public function passes($attribute, $value): bool
    {
        /** @var Product $product */
        $voucher = Voucher::findByAddressOrPhysicalCard($this->voucherAddress);
        $product = ProductSubQuery::appendReservationStats([
            'voucher_id' => $voucher->id ?? null,
        ], Product::whereId($value))->first();

        if (!$this->voucherAddress || !$voucher) {
            return $this->rejectTrans('voucher_address_required');
        }

        if (!$product || !$product->exists) {
            return $this->rejectTrans('product_not_found');
        }

        if (!$product->reservationsEnabled($voucher->fund)) {
            return $this->rejectTrans('reservation_not_enabled');
        }

        if ($product->sold_out) {
            return $this->rejectTrans('product_sold_out');
        }

        if ($this->priceType && ($product->price_type !== $this->priceType)) {
            return $this->rejectTrans('invalid_product_price_type');
        }

        if ($voucher->fund->isTypeBudget() && ($product->price > $voucher->amount_available)) {
            return $this->rejectTrans('not_enough_voucher_funds');
        }

        // validate per-identity limit
        if ($voucher->fund->isTypeSubsidy()) {
            if (($product['limit_total_available'] ?? 0) < 1) {
                return $this->reject(trans('validation.product_reservation.no_total_stock'));
            }

            if (($product['limit_available'] ?? 0) < 1) {
                return $this->reject(trans('validation.product_reservation.no_identity_stock'));
            }
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(
            Product::query(),
            $voucher->fund_id
        )->where('id', $product->id)->exists() || $this->reject('Product not available.');
    }
}
