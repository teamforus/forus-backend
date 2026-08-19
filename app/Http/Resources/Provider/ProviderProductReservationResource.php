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
        ...parent::LOAD,
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
            ...$reservation->only('invoice_number', 'accepted_note'),
            'acceptable' => $reservation->isAcceptable(),
            'rejectable' => $reservation->isCancelableByProvider(),
            'archivable' => $reservation->isArchivable(),
            'allow_provider_messages' => $reservation->regularProviderMessageAllowed(),
        ];
    }
}
