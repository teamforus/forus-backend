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
        return array_merge(
            $this->resource->only('id', 'state', 'state_locale', 'amount'), [
            'amount_locale' => currency_format_locale($this->resource->amount),
        ], $this->makeTimestamps($this->resource->only('created_at')));
    }
}
