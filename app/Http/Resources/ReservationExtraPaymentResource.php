<?php

namespace App\Http\Resources;

use App\Models\ReservationExtraPayment;
use Illuminate\Http\Request;

/**
 * @property-read ReservationExtraPayment $resource
 */
class ReservationExtraPaymentResource extends BaseJsonResource
{
    public const array LOAD = [
        'refunds_active',
        'refunds_completed',
    ];

    public const array LOAD_NESTED = [
        'refunds' => ReservationExtraPaymentRefundResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
