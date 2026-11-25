<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\ProductReservationResource;
use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * @property ProductReservation $resource
 */
class ProviderProductReservationResource extends ProductReservationResource
{
    public const array LOAD = [
        ...parent::LOAD,
        'product.organization.fund_providers',
    ];

    /**
     * @param string|null $append
     * @return array
     */
    public static function load(?string $append = null): array
    {
        return [
            ...parent::load($append),
            ($append ? "$append.product" : 'product') => static fn (BelongsTo $product) => $product->withCount([
                'product_reservations_pending', 'voucher_transactions_sold',
            ]),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $reservation = $this->resource;

        return [
            ...parent::toArray($request),
            ...$reservation->only('invoice_number', 'accepted_note'),
            'acceptable' => $reservation->isAcceptable(),
            'rejectable' => $reservation->isCancelableByProvider(),
            'archivable' => $reservation->isArchivable(),
            'allow_provider_messages' => $reservation->regularProviderMessageAllowed(),
        ];
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function productData(ProductReservation $reservation): array
    {
        $product = $reservation->product;

        return [
            ...parent::productData($reservation),
            ...$product->only([
                'expired', 'unlimited_stock', 'stock_amount', 'ean', 'sku',
            ]),
            'sold_amount' => $product->countSoldCached(),
            'reserved_amount' => $product->countReservedCached(),
            ...$this->makeTimestamps($product->only([
                'expire_at',
            ]), dateOnly: true),
        ];
    }
}
