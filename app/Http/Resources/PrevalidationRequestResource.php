<?php

namespace App\Http\Resources;

use App\Http\Resources\Small\FundSmallResource;
use App\Models\PrevalidationRequest;
use Illuminate\Http\Request;

/**
 * @property PrevalidationRequest $resource
 */
class PrevalidationRequestResource extends BaseJsonResource
{
    public const array LOAD = [
        'latest_failed_log',
        'employee.identity.primary_email',
        'missed_records',
    ];

    public const array LOAD_NESTED = [
        'records' => PrevalidationRequestRecordResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $reason = $this->resource->failed_reason;

        return [
            ...$this->resource->only([
                'id', 'bsn', 'state', 'fund_id', 'employee_id', 'missing_records_approved',
            ]),
            'failed_reason' => $reason,
            'failed_reason_locale' => $reason ? trans("prevalidation_requests.reasons.$reason") : null,
            'employee' => $this->resource->employee ? [
                ...$this->resource->employee->only([
                    'id', 'organization_id', 'identity_address',
                ]) ?? [],
                'email' => $this->resource->employee->identity?->email,
            ] : null,
            'fund' => FundSmallResource::create($this->resource->fund),
            'record_groups' => $this->resource->fund->getRecordGroups($this->resource->records),
            'records' => PrevalidationRequestRecordResource::collection($this->resource->records),
            'missed_records' => PrevalidationRequestMissedRecordResource::collection($this->resource->missed_records),
            ...$this->makeTimestamps($this->resource->only(['created_at'])),
        ];
    }
}
