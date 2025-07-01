<?php

namespace App\Rules\ProductReservations;

use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
use App\Models\Voucher;
use App\Rules\BaseRule;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;
use Exception;
use Illuminate\Support\Env;

class ProductIdToReservationRule extends BaseRule
{
    protected string $messageTransPrefix = 'validation.product_reservation.';
    private int $throttleTotalPendingCount;
    private int $throttleRecentlyCanceledCount;

    /**
     * Create a new rule instance.
     *
     * @param int|null $voucherId
     * @param bool $throttle
     * @param bool $allowExtraPayment
     */
    public function __construct(
        private readonly ?int $voucherId,
        private readonly bool $throttle = false,
        private readonly bool $allowExtraPayment = false,
    ) {
        $this->throttleTotalPendingCount = Env::get('RESERVATION_THROTTLE_TOTAL_PENDING', 100);
        $this->throttleRecentlyCanceledCount = Env::get('RESERVATION_THROTTLE_RECENTLY_CANCELED', 10);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @throws Exception
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        /** @var Product $product */
        $voucher = Voucher::whereId($this->voucherId)->first();
        $product = ProductSubQuery::appendReservationStats([
            'voucher_id' => $voucher->id ?? null,
        ], Product::whereId($value))->first();

        $providerProduct = $product->getFundProviderProduct($voucher->fund);
        $userPrice = $product->fundPrice($voucher->fund);

        if (!$this->voucherId || !$voucher) {
            return $this->rejectTrans('voucher_address_required');
        }

        if (!$product || !$product->exists) {
            return $this->rejectTrans('product_not_found');
        }

        if (!$product->reservationsEnabled()) {
            return $this->rejectTrans('reservation_not_enabled');
        }

        if ($product->sold_out) {
            return $this->rejectTrans('product_sold_out');
        }

        if (!$voucher->fund->fund_config->allow_reservations) {
            return $this->rejectTrans('reservation_not_allowed_by_fund');
        }

        if (
            $userPrice > $voucher->amount_available &&
            (!$this->isExtraPaymentEnabled($voucher, $product) || $providerProduct?->isPaymentTypeSubsidy())
        ) {
            return $this->rejectTrans('not_enough_voucher_funds');
        }

        // validate total limit
        if (!$this->hasStock($product['limit_total_available'] ?? null)) {
            return $this->reject(trans('validation.product_reservation.no_total_stock'));
        }

        // validate voucher limit
        if (!$this->hasStock($product['limit_available'] ?? null)) {
            return $this->reject(trans('validation.product_reservation.no_identity_stock'));
        }

        // multiple reservations with unpaid extra by the same vouchers are not allowed
        if ($this->allowExtraPayment && $product->product_reservations()
            ->where('state', ProductReservation::STATE_WAITING)
            ->where('voucher_id', $voucher->id)
            ->whereRelation('extra_payment', 'state', '!=', ReservationExtraPayment::STATE_PAID)
            ->exists()) {
            return $this->reject(trans('validation.product_reservation.reservations_has_unpaid_extra'));
        }

        if ($this->throttle && $product->product_reservations()->whereIn(
            'state',
            [ProductReservation::STATE_PENDING]
        )->where('voucher_id', $voucher->id)->count() >= $this->throttleTotalPendingCount) {
            return $this->reject(trans('validation.product_reservation.reservations_limit_reached', [
                'count' => $this->throttleTotalPendingCount,
            ]));
        }

        if ($this->throttle && $product->product_reservations()->where([
            'voucher_id' => $voucher->id,
            'state' => ProductReservation::STATE_CANCELED_BY_CLIENT,
        ])->where('canceled_at', '>=', now()->subHour())->count() >= $this->throttleRecentlyCanceledCount) {
            return $this->reject(trans('validation.product_reservation.too_many_canceled_reservations_for_product', [
                'count' => $this->throttleRecentlyCanceledCount,
            ]));
        }

        // check validity
        return ProductQuery::approvedForFundsAndActiveFilter(Product::query(), $voucher->fund_id)
            ->where('id', $product->id)
            ->exists() || $this->reject('Product not available.');
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return bool
     */
    protected function isExtraPaymentEnabled(Voucher $voucher, Product $product): bool
    {
        if (!$this->allowExtraPayment) {
            return true;
        }

        return $product->reservationExtraPaymentsEnabled($voucher->fund, $voucher->amount_available);
    }

    /**
     * @param ?int $limit
     * @return bool
     */
    protected function hasStock(?int $limit): bool
    {
        return is_null($limit) || ($limit > 0);
    }
}
