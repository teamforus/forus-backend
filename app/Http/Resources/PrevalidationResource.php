<?php

namespace App\Http\Resources;

use App\Models\Prevalidation;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class PrevalidationResource
 * @property Prevalidation $resource
 * @package App\Http\Resources
 */
class PrevalidationResource extends Resource
{
    public static $load = [
        'prevalidation_records.record_type.translations'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fields = array_merge([
            'id', 'uid', 'records_hash', 'uid_hash', 'state', 'exported', 'fund_id',
        ], auth()->id() === $this->resource->identity_address ? [
            'created_at', 'identity_address'
        ] : []);

        return array_merge($this->resource->only($fields), [
            'records' => PrevalidationRecordResource::collection(
                $this->resource->prevalidation_records->sortByDesc('record_type_id')
            )
        ]);
    }
}
