<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseProductReservationResource;
use App\Models\ProductReservation;
use Illuminate\Http\Request;

/**
 * @property ProductReservation $resource
 */
class SponsorProductReservationResource extends BaseProductReservationResource
{
    public const array LOAD = [
        'voucher.fund.fund_config',
        'voucher.identity.primary_email',
        'product.organization',
        'product.photos.presets',
        'voucher_transaction',
        'extra_payment.refunds',
        'extra_payment.refunds_active',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $reservation = $this->resource;
        $voucher = $this->resource->voucher;
        $transaction = $this->resource->voucher_transaction;

        return [
            ...$reservation->only([
                'id', 'state', 'state_locale', 'amount', 'code', 'amount_extra',
                'first_name', 'last_name', 'user_note', 'phone', 'address', 'archived',
            ]),
            'amount_locale' => currency_format_locale($reservation->amount),
            'expired' => $reservation->isExpired(),
            'canceled' => $reservation->isCanceled(),
            'product' => $this->productData($reservation),
            'voucher_transaction' => $transaction?->only('id', 'address', 'state', 'state_locale'),
            'identity_email' => $voucher->identity?->email,
            ...$this->getProductPrice($reservation),
            ...$this->extraPaymentData($reservation),
            ...$this->makeTimestamps($reservation->only([
                'created_at', 'accepted_at', 'rejected_at', 'canceled_at', 'expire_at', 'birth_date',
            ]), true),
        ];
    }
}
