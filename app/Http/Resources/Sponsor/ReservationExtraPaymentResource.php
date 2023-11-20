<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ProductReservationResource;
use App\Http\Resources\ReservationExtraPaymentRefundResource;
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
            'is_paid' => $this->resource->isPaid(),
            'refunds' => ReservationExtraPaymentRefundResource::collection($this->resource->refunds),
            'is_pending' => $this->resource->isPending(),
            'reservation' => new ProductReservationResource($this->resource->product_reservation),
            'amount_locale' => currency_format_locale($this->resource->amount),
            'is_full_refunded' => $this->resource->isFullRefunded(),
        ], $this->makeTimestamps($this->resource->only([
            'created_at', 'paid_at', 'canceled_at',
        ])));
    }
}
