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
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $creatorFields = [];

        if (auth()->id() == $this->resource->identity_address) {
            $creatorFields = collect($this->resource)->only([
                'fund_id', 'created_at', 'identity_address'
            ]);
        }

        return collect($this->resource)->only([
            'id', 'uid', 'state', 'exported', 'fund_id',
        ])->merge($creatorFields)->merge([
            'records' => PrevalidationRecordResource::collection(
                $this->resource->prevalidation_records->filter(function($record) {
                    return strpos($record->record_type->key, '_eligible') === false;
                })->sortByDesc('record_type_id')
            )
        ])->toArray();
    }
}
