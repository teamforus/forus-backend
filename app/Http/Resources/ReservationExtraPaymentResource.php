<?php

namespace App\Http\Resources;

use App\Models\ReservationExtraPayment;

/**
 * @property-read ReservationExtraPayment $resource
 */
class ReservationExtraPaymentResource extends BaseJsonResource
{
    public const LOAD = [
        'refunds',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return ([
            ...$this->resource->only([
                'id', 'type', 'state', 'state_locale', 'amount', 'amount_locale', 'method',
            ]),
            'is_paid' => $this->resource->isPaid(),
            'is_pending' => $this->resource->isPending(),
            'is_refundable' => $this->resource->isRefundable(),
            'is_fully_refunded' => $this->resource->isFullyRefunded(),
            'refunds' => ReservationExtraPaymentRefundResource::collection($this->resource->refunds),
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'paid_at', 'canceled_at',
            ])),
        ]);
    }
}
