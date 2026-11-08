<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\PhysicalCardRequest;
use Illuminate\Http\Request;

/**
 * @property PhysicalCardRequest $resource
 */
class SponsorPhysicalCardRequestResource extends BaseJsonResource
{
    public const array LOAD = [
        'dcp_order',
        'physical_card_type',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $cardRequest = $this->resource;
        $dcpOrder = $cardRequest->dcp_order;

        return [
            ...$cardRequest->only([
                'id', 'address', 'house', 'house_addition', 'postcode', 'city', 'first_name', 'last_name',
                'card_code',
            ]),
            'physical_card_type' => $cardRequest->physical_card_type?->only(['id', 'name']),
            'dcp_order' => $dcpOrder ? [
                ...$dcpOrder->only([
                    'id', 'order_id', 'order_id_dcp', 'dcp_order_bulk_id', 'state', 'status_id', 'status_message',
                    'attempts',
                ]),
                'status_locale' => $dcpOrder->statusLocale(),
                ...$this->makeTimestamps($dcpOrder->only(['shipped_at', 'last_attempt_at'])),
            ] : null,
            ...$this->makeTimestamps($cardRequest->only(['created_at'])),
        ];
    }
}
