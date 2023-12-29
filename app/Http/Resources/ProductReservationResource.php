<?php

namespace App\Http\Resources;

use App\Models\Identity;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;

/**
 * @property ProductReservation $resource
 */
class ProductReservationResource extends BaseJsonResource
{
    public const LOAD = [
        'voucher.fund.organization',
        'product.organization',
        'product.photo.presets',
        'voucher_transaction',
        'extra_payment.refunds',
        'extra_payment.refunds_active',
        'custom_fields.organization_reservation_field'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $reservation = $this->resource;
        $voucher = $this->resource->voucher;
        $transaction = $this->resource->voucher_transaction;

        $productSnapshot = new Product(array_merge($reservation->only([
            'price_type', 'price_discount'
        ]), $voucher->fund->isTypeSubsidy() ? [
            'price' => is_null($reservation->price) ? null : max($reservation->price - $reservation->amount, 0),
        ] : [
            'price' => $reservation->price,
        ]));

        $price = is_null($productSnapshot->price) ? null : currency_format($productSnapshot->price);

        if ($reservation->price_type === 'regular' && ($price === currency_format(0))) {
            $price_locale = 'Gratis';
        } else {
            $price_locale = $productSnapshot->price_locale;
        }

        return [
            ...$reservation->only([
                'id', 'state', 'state_locale', 'amount', 'code', 'amount_extra',
                'first_name', 'last_name', 'user_note', 'phone', 'address', 'archived',
            ]),
            'price' => $price,
            'price_locale' => $price_locale,
            'amount_locale' => currency_format_locale($reservation->amount),
            'expired' => $reservation->hasExpired(),
            'canceled' => $reservation->isCanceled(),
            'cancelable' => $reservation->isCancelableByRequester(),
            'acceptable' => $reservation->isAcceptable(),
            'rejectable' => $reservation->isCancelableByProvider(),
            'archivable' => $reservation->isArchivable(),
            'product' => array_merge($reservation->product->only('id', 'name', 'organization_id'), [
                'deleted' => $reservation->product->trashed(),
                'organization' => $reservation->product->organization->only('id', 'name'),
                'photo' => new MediaResource($reservation->product->photo),
            ]),
            'fund' => [
                ...$voucher->fund->only('id', 'name'),
                'organization' => $voucher->fund->organization->only('id', 'name'),
            ],
            'voucher_transaction' => $transaction?->only('id', 'address'),
            'custom_fields' => ProductReservationFieldValueResource::collection($reservation->custom_fields),
            ...$this->identityData($reservation, $voucher),
            ...$this->extraPaymentData($reservation),
            ...$this->makeTimestamps($reservation->only([
                'created_at', 'accepted_at', 'rejected_at', 'canceled_at', 'expire_at', 'birth_date',
            ]), true),
        ];
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function extraPaymentData(ProductReservation $reservation): array
    {
        return [
            'amount_extra' => currency_format($reservation->amount_extra),
            'amount_extra_locale' => currency_format_locale($reservation->amount_extra),
            'extra_payment' => new ReservationExtraPaymentResource($reservation->extra_payment),
            'extra_payment_expires_in' => $reservation->expiresIn(),
        ];
    }

    /**
     * @param ProductReservation $reservation
     * @param Voucher $voucher
     * @return array
     */
    protected function identityData(ProductReservation $reservation, Voucher $voucher): array
    {
        $physicalCard = $voucher->physical_cards[0] ?? null;

        return $reservation->product->organization->identityCan(Identity::auth(), 'scan_vouchers') ? [
            'identity_email' => $voucher->identity?->email,
            'identity_physical_card' => $physicalCard->code ?? null,
        ] : [];
    }
}
