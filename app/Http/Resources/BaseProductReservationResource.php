<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductReservation;

class BaseProductReservationResource extends BaseJsonResource
{
    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function getProductPrice(ProductReservation $reservation): array
    {
        $voucher = $reservation->voucher;

        $productSnapshot = new Product(array_merge($reservation->only([
            'price_type', 'price_discount',
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

        return compact('price', 'price_locale');
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function productData(ProductReservation $reservation): array
    {
        return [
            'id' => $reservation->product->id,
            ...$reservation->product->translateColumns($reservation->product->only('name')),
            'deleted' => $reservation->product->trashed(),
            'organization_id' => $reservation->product->organization_id,
            'organization' => [
                'id' => $reservation->product->organization->id,
                'name' => $reservation->product->organization->name,
            ],
            'photo' => new MediaResource($reservation->product->photo),
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
}
