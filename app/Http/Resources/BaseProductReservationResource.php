<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductReservation;

class BaseProductReservationResource extends BaseJsonResource
{
    public const array LOAD = [
        'extra_payment',
        'fund_provider_product_with_trashed',
        'product',
        'product.organization',
        'product.photos',
    ];

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function getProductPrice(ProductReservation $reservation): array
    {
        $productSnapshot = new Product([
            ...$reservation->only([
                'price_type', 'price_discount',
            ]),
            ...($reservation->fund_provider_product_with_trashed?->isPaymentTypeSubsidy()) ? [
                'price' => is_null($reservation->amount_voucher)
                    ? ($reservation->amount ?: $reservation->price)
                    : $reservation->amount_voucher,
            ] : [
                'price' => $reservation->price,
            ],
        ]);

        $productSnapshotProvider = new Product($reservation->only([
            'price_type', 'price_discount', 'price',
        ]));

        return [
            'price' => $productSnapshotProvider->price,
            'price_locale' => $productSnapshotProvider->price_locale,
            'price_voucher' => $productSnapshot->price,
            'price_voucher_locale' => $productSnapshot->price_locale,
        ];
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
            'price' => $reservation->product->price,
            'price_locale' => $reservation->product->price_locale,
            'deleted' => $reservation->product->trashed(),
            'organization_id' => $reservation->product->organization_id,
            'organization' => [
                'id' => $reservation->product->organization->id,
                'name' => $reservation->product->organization->name,
            ],
            'photos' => MediaResource::collection($reservation->product->photos),
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
