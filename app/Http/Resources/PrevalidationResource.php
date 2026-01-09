<?php

namespace App\Http\Resources;

use App\Http\Resources\Small\FundSmallResource;
use App\Models\Prevalidation;
use Illuminate\Http\Request;

/**
 * @property Prevalidation $resource
 */
class PrevalidationResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund',
        'prevalidation_records.record_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $prevalidation = $this->resource;
        $records = $prevalidation->prevalidation_records->sortByDesc('record_type_id');

        return [
            ...$prevalidation->only([
                'id', 'uid', 'records_hash', 'uid_hash', 'state', 'exported', 'fund_id',
                'identity_address',
            ]),
            'records' => PrevalidationRecordResource::collection($records),
            'fund' => FundSmallResource::create($prevalidation->fund),
            ...$this->makeTimestamps($this->resource->only(['created_at'])),
        ];
    }
}
