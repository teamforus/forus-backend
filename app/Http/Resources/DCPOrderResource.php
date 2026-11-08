<?php

namespace App\Http\Resources;

use App\Services\DCPService\Contracts\DCPOrderable;
use App\Services\DCPService\Models\DCPOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @property DCPOrder $resource
 */
class DCPOrderResource extends BaseJsonResource
{
    public const array LOAD = [
        'orderable',
        'dcp_connection.implementation',
        'payload_edited_by_employee.identity.primary_email',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $address = (array) Arr::get($order->payload ?: [], 'deliveryAddress', []);
        $orderable = $order->orderable;

        return [
            ...$order->only([
                'id', 'organization_id', 'dcp_order_bulk_id', 'orderable_type', 'orderable_id', 'order_id',
                'order_id_dcp', 'state', 'status_id', 'status_message', 'attempts', 'response_code',
            ]),
            'status_locale' => $order->statusLocale(),
            'label' => Arr::get($order->payload ?: [], 'label'),
            'item' => Arr::get($order->payload ?: [], 'orderRows.0.item'),
            'delivery_address' => Arr::only($address, ['fullname', 'address1', 'zipcode', 'city', 'country']),
            'delivery_address_fields' => $order->deliveryAddressFields(),
            'tag_fields' => $order->tagFields(),
            'validation_errors' => $order->validationErrors(),
            'orderable' => $orderable instanceof DCPOrderable ? $orderable->dcpOrderableDetails() : null,
            'implementation' => $order->dcp_connection?->implementation?->only(['id', 'name']),
            'dcp_connection_active' => (bool) $order->dcp_connection?->isActive(),
            'is_editable' => $order->isEditable(),
            'is_duplicate' => $order->isDuplicate(),
            'payload_edited_by_employee_email' => $order->payload_edited_by_employee?->identity?->email,
            ...$this->makeTimestamps($order->only([
                'created_at', 'shipped_at', 'last_attempt_at', 'payload_edited_at',
            ])),
        ];
    }
}
