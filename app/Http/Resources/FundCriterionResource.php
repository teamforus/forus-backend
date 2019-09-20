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
        return collect($this->resource)->only([
            'id', 'record_type_key', 'operator', 'value'
        ])->toArray();
    }
}
