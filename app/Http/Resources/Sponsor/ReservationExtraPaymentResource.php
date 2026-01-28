<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\ProductReservationResource;
use App\Http\Resources\ReservationExtraPaymentRefundResource;
use App\Models\ReservationExtraPayment;
use Illuminate\Http\Request;

/**
 * @property-read ReservationExtraPayment $resource
 */
class ReservationExtraPaymentResource extends BaseJsonResource
{
    public const array LOAD = [
        'refunds_completed',
    ];

    public const array LOAD_NESTED = [
        'refunds' => ReservationExtraPaymentRefundResource::class,
        'product_reservation' => ProductReservationResource::class,
    ];

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
                'id', 'type', 'state', 'state_locale', 'amount', 'amount_locale', 'method',
            ]),
            'is_paid' => $this->resource->isPaid(),
            'is_pending' => $this->resource->isPending(),
            'is_fully_refunded' => $this->resource->isFullyRefunded(),
            'refunds' => ReservationExtraPaymentRefundResource::collection($this->resource->refunds),
            'reservation' => new ProductReservationResource($this->resource->product_reservation),
            ...$this->makeTimestamps($this->resource->only([
                'created_at', 'paid_at', 'canceled_at',
            ])),
        ];
    }
}
