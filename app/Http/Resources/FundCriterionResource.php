<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

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
            'record_type_key', 'operator', 'value'
        ])->toArray();
    }
}
