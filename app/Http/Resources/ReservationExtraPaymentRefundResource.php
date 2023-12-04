<?php

namespace App\Http\Resources;

use App\Models\ReservationExtraPaymentRefund;

/**
 * @property-read ReservationExtraPaymentRefund $resource
 */
class ReservationExtraPaymentRefundResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            ...$this->resource->only([
                'id', 'state', 'state_locale', 'amount', 'amount_locale',
            ]),
            ...$this->makeTimestamps($this->resource->only('created_at')),
        ];
    }
}
