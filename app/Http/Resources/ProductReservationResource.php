<?php

namespace App\Http\Resources;

use App\Models\Identity;
use App\Models\Product;
use App\Models\ProductReservation;

/**
 * @property ProductReservation $resource
 */
class ProductReservationResource extends BaseJsonResource
{
    public const LOAD = [
        'voucher.fund',
        'product.organization',
        'product.photo.presets',
        'voucher_transaction',
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

        $physicalCard = $voucher->physical_cards[0] ?? null;
        $identityData = $reservation->product->organization->identityCan(Identity::auth(), 'scan_vouchers') ? [
            'identity_email' => $voucher->identity?->email,
            'identity_physical_card' => $physicalCard ? $physicalCard->code : null,
        ] : [];

        $price = is_null($productSnapshot->price) ? null : currency_format($productSnapshot->price);

        if (($price === currency_format(0)) && $reservation->price_type == 'regular') {
            $price_locale = 'Gratis';
        } else {
            $price_locale = $productSnapshot->price_locale;
        }

        return array_merge($reservation->only([
            'id', 'state', 'state_locale', 'amount', 'code',
            'first_name', 'last_name', 'user_note', 'phone', 'address', 'birth_date',
        ]), [
            'price' => $price,
            'price_locale' => $price_locale,
            'expired' => $reservation->hasExpired(),
            'canceled' => $reservation->isCanceled(),
            'product' => array_merge($reservation->product->only('id', 'name', 'organization_id'), [
                'deleted' => $reservation->product->trashed(),
                'organization' => $reservation->product->organization->only('id', 'name'),
                'photo' => new MediaResource($reservation->product->photo),
            ]),
            'fund' => array_merge($voucher->fund->only([
                'id', 'name',
            ]), [
                'organization' => $voucher->fund->organization->only('id', 'name'),
            ]),
            'voucher_transaction' => $transaction?->only('id', 'address'),
            'birth_date_locale' => format_date_locale($reservation->birth_date),
        ], $this->getReservationDates($reservation), $identityData);
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function getReservationDates(ProductReservation $reservation): array
    {
        return [
            'created_at' => $reservation->created_at?->format('Y-m-d H:i:s'),
            'created_at_locale' => format_date_locale($reservation->created_at),
            'accepted_at' => $reservation->accepted_at?->format('Y-m-d H:i:s'),
            'accepted_at_locale' => format_date_locale($reservation->accepted_at),
            'rejected_at' => $reservation->rejected_at?->format('Y-m-d H:i:s'),
            'rejected_at_locale' => format_date_locale($reservation->rejected_at),
            'canceled_at' => $reservation->canceled_at?->format('Y-m-d H:i:s'),
            'canceled_at_locale' => format_date_locale($reservation->canceled_at),
            'expire_at' => $reservation->expire_at->format('Y-m-d H:i:s'),
            'expire_at_locale' => format_date_locale($reservation->expire_at),
        ];
    }
}
