<?php

namespace App\Http\Resources;

use App\Models\FundCriterion;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundCriterionResource
 * @property FundCriterion $resource
 * @package App\Http\Resources
 */
class FundCriterionResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $recordTypes = array_pluck(record_types_cached(), 'name', 'key');

        return collect($this->resource)->only([
            'id', 'record_type_key', 'operator', 'value'
        ])->merge([
            'record_type_name' => $recordTypes[$this->resource->record_type_key]
        ])->toArray();
    }
}
