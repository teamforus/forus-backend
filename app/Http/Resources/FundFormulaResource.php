<?php

namespace App\Http\Resources;

use App\Models\FundFormula;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundCriterionResource
 * @property FundFormula $resource
 * @package App\Http\Resources
 */
class FundFormulaResource extends Resource
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
            'type', 'amount', 'record_type_key'
        ])->toArray();
    }
}
