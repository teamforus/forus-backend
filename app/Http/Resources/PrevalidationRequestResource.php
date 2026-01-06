<?php

namespace App\Http\Resources;

use App\Http\Resources\Small\FundSmallResource;
use App\Models\PrevalidationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @property PrevalidationRequest $resource
 */
class PrevalidationRequestResource extends BaseJsonResource
{
    public const array LOAD = [
        'latest_failed_log',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $reason = Arr::get($this->resource->latest_failed_log?->data ?? [], 'prevalidation_request_failed_reason');

        return [
            ...$this->resource->only([
                'id', 'bsn', 'state', 'fund_id', 'employee_id',
            ]),
            'failed_reason' => $reason,
            'failed_reason_locale' => $reason ? trans("prevalidation_requests.reasons.$reason") : null,
            'employee' => [
                ...$this->resource->employee->only([
                    'id', 'organization_id', 'identity_address',
                ]),
                'email' => $this->resource->employee->identity?->email,
            ],
            'fund' => FundSmallResource::create($this->resource->fund),
            ...$this->makeTimestamps($this->resource->only(['created_at'])),
        ];
    }
}
