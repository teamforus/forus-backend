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
    public const LOAD = [
        'refunds',
        'product_reservation.voucher.fund.organization',
        'product_reservation.product.organization',
        'product_reservation.product.photo.presets',
        'product_reservation.voucher_transaction',
        'product_reservation.extra_payment.refunds',
        'product_reservation.custom_fields.organization_reservation_field'
    ];

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
