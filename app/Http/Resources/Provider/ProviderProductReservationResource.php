<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\ProductReservationResource;
use App\Models\ProductReservation;
use Illuminate\Http\Request;

/**
 * @property ProductReservation $resource
 */
class ProviderProductReservationResource extends ProductReservationResource
{
    public const array LOAD = [
        'voucher.fund.fund_config',
        'voucher.fund.organization',
        'voucher.identity.primary_email',
        'voucher.physical_cards',
        'voucher.voucher_records',
        'product.organization',
        'product.photos.presets',
        'voucher_transaction',
        'extra_payment.refunds',
        'extra_payment.refunds_active',
        'custom_fields.reservation_field',
        'custom_fields.files',
        'fund_provider_product_with_trashed',
        'product.organization.fund_providers',
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

        return [
            ...parent::toArray($request),
            ...$reservation->only('invoice_number'),
            'acceptable' => $reservation->isAcceptable(),
            'rejectable' => $reservation->isCancelableByProvider(),
            'archivable' => $reservation->isArchivable(),
            'allow_provider_messages' => $reservation->providerMessageAllowed(),
        ];
    }
}
