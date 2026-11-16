<?php

namespace App\Modules\DCP\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\DCP\Models\DCPOrderBulk;
use Illuminate\Http\Request;

/**
 * @property-read DCPOrderBulk $resource
 */
class DCPOrderBulkResource extends BaseJsonResource
{
    public const array LOAD = ['employee.identity.primary_email'];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $bulk = $this->resource;
        $counts = [];

        foreach (DCPOrderState::cases() as $state) {
            $counts[$state->value] = (int) $bulk->getAttribute('dcp_orders_' . $state->value . '_count');
        }

        return [
            ...$bulk->only([
                'id', 'organization_id', 'employee_id',
            ]),
            'employee_email' => $bulk->employee?->identity?->email,
            'dcp_orders_count' => (int) $bulk->getAttribute('dcp_orders_count'),
            'counts' => $counts,
            'submitted_count' => $counts[DCPOrderState::ACCEPTED->value] + $counts[DCPOrderState::CLOSED->value] +
                $counts[DCPOrderState::CANCELED->value],
            'shipped_count' => (int) $bulk->getAttribute('dcp_orders_shipped_count'),
            ...$this->makeTimestamps($bulk->only([
                'approved_at', 'created_at', 'updated_at',
            ])),
        ];
    }
}
