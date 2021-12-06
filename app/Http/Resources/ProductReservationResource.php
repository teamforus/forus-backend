<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductReservation;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProductReservationResource
 * @property ProductReservation $resource
 * @package App\Http\Resources
 */
class ProductReservationResource extends JsonResource
{
    /**
     * @return array
     */
    public static function load(): array
    {
        return [
            'voucher.fund',
            'product.organization',
        ];
    }

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
        $identityData = $reservation->product->organization->identityCan('scan_vouchers') ? [
            'identity_email' => $voucher->identity->primary_email->email ?? null,
            'identity_physical_card' => $physicalCard ? $physicalCard->code : null,
        ] : [];

        $price = is_null($productSnapshot->price) ? null : currency_format($productSnapshot->price);

        if (($price === currency_format(0)) && $reservation->price_type == 'regular') {
            $price_locale = 'Gratis';
        } else {
            $price_locale = $productSnapshot->price_locale;
        }

        return array_merge($reservation->only([
            'id', 'state', 'state_locale', 'amount', 'code', 'first_name', 'last_name', 'user_note',
        ]), [
            'created_at' => $reservation->created_at ? $reservation->created_at->format('Y-m-d H:i:s') : null,
            'created_at_locale' => format_date_locale($reservation->created_at),
            'accepted_at' => $reservation->accepted_at ? $reservation->accepted_at->format('Y-m-d H:i:s') : null,
            'accepted_at_locale' => format_date_locale($reservation->accepted_at),
            'rejected_at' => $reservation->rejected_at ? $reservation->rejected_at->format('Y-m-d H:i:s') : null,
            'rejected_at_locale' => format_date_locale($reservation->rejected_at),
            'canceled_at' => $reservation->canceled_at ? $reservation->canceled_at->format('Y-m-d H:i:s') : null,
            'canceled_at_locale' => format_date_locale($reservation->canceled_at),
            'expire_at' => $reservation->expire_at ? $reservation->expire_at->format('Y-m-d H:i:s') : null,
            'expire_at_locale' => format_date_locale($reservation->expire_at),

            'expired' => $reservation->hasExpired(),
            'product' => array_merge($reservation->product->only('id', 'name'), [
                'deleted' => $reservation->product->trashed(),
                'organization' => $reservation->product->organization->only('id', 'name')
            ]),
            'fund' => $voucher->fund->only('id', 'name'),
            'voucher_transaction' => $transaction ? $transaction->only('id', 'address') : null,
        ], $identityData, compact('price', 'price_locale'));
    }
}
