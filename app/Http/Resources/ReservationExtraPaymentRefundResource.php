<?php

namespace App\Http\Resources;

use App\Models\ReservationExtraPaymentRefund;
use Illuminate\Http\Request;

/**
 * @property-read ReservationExtraPaymentRefund $resource
 */
class ReservationExtraPaymentRefundResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'state', 'state_locale', 'amount', 'amount_locale',
            ]),
            ...$this->makeTimestamps($this->resource->only('created_at')),
        ];
    }
}
