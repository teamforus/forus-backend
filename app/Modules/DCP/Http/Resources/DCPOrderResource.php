<?php

namespace App\Modules\DCP\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Modules\DCP\Contracts\DCPOrderable;
use App\Modules\DCP\Enums\DCPOrderStateGroup;
use App\Modules\DCP\Enums\DCPRemoteStatus;
use App\Modules\DCP\Models\DCPOrder;
use Illuminate\Http\Request;

/**
 * @property-read DCPOrder $resource
 */
class DCPOrderResource extends BaseJsonResource
{
    public const array LOAD = ['orderable', 'dcp_connection.implementation', 'employee.identity.primary_email'];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $connection = $order->dcp_connection;
        $source = $order->orderable;

        return [
            ...$order->only([
                'id', 'organization_id', 'dcp_connection_id', 'dcp_order_bulk_id', 'employee_id', 'orderable_type',
                'orderable_id', 'order_id', 'order_id_dcp', 'label', 'status_id', 'status_message', 'diagnostic_id',
            ]),
            'state' => $order->state->value,
            'state_locale' => trans('dcp::states.orders.' . $order->state->value),
            'state_group' => DCPOrderStateGroup::forState($order->state)->value,
            'status_locale' => DCPRemoteStatus::label($order->status_id),
            'source' => $source instanceof DCPOrderable && $source->dcpOrganizationId() === $order->organization_id ? [
                'type' => $order->orderable_type,
                'id' => $order->orderable_id,
                'code' => $source->dcpPrintCode(),
            ] : null,
            'connection' => [
                'id' => $connection->id,
                'environment' => $connection->environment->value,
                'implementation' => $connection->implementation->only([
                    'id', 'name',
                ]),
            ],
            'delivery_address' => [
                'first_name' => $order->delivery_first_name,
                'last_name' => $order->delivery_last_name,
                'address1' => $order->delivery_address1,
                'postal_code' => $order->delivery_postal_code,
                'city' => $order->delivery_city,
                'country' => $order->delivery_country,
            ],
            'order_rows' => $order->order_rows,
            'employee_email' => $order->employee?->identity?->email,
            ...$this->makeTimestamps($order->only([
                'approved_at', 'sending_at', 'submitted_at', 'last_status_checked_at', 'shipped_at',
                'created_at', 'updated_at',
            ])),
        ];
    }
}
