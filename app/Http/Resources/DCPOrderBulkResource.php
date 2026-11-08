<?php

namespace App\Http\Resources;

use App\Services\DCPService\Models\DCPOrderBulk;
use Illuminate\Http\Request;

/**
 * @property DCPOrderBulk $resource
 */
class DCPOrderBulkResource extends BaseJsonResource
{
    public const array LOAD = [
        'employee.identity.primary_email',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $bulk = $this->resource;

        if (is_null($bulk->dcp_orders_count)) {
            $bulk->loadCount(DCPOrderBulk::orderCounts());
        }

        return [
            ...$bulk->only([
                'id', 'organization_id', 'state', 'dcp_orders_count', 'dcp_orders_pending_count',
                'dcp_orders_sent_count', 'dcp_orders_error_count',
            ]),
            'state_locale' => $bulk->stateLocale(),
            'employee_email' => $bulk->employee?->identity?->email,
            ...$this->makeTimestamps($bulk->only(['created_at', 'processed_at'])),
        ];
    }
}
