<?php

namespace App\Http\Resources;

use App\Models\ReservationExtraPayment;

/**
 * @property-read ReservationExtraPayment $resource
 */
class ReservationExtraPaymentResource extends BaseJsonResource
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
            $this->resource->only('id', 'type', 'state', 'state_locale', 'amount', 'method'), [
            'amount_locale' => currency_format_locale($this->resource->amount),
            'is_paid' => $this->resource->isPaid(),
            'is_pending' => $this->resource->isPending(),
            'is_full_refunded' => $this->resource->isFullRefunded(),
            'refunds' => ReservationExtraPaymentRefundResource::collection($this->resource->refunds),
        ], $this->makeTimestamps($this->resource->only([
            'created_at', 'paid_at', 'canceled_at',
        ])));
    }
}
