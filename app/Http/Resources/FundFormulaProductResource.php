<?php

namespace App\Http\Resources;

use App\Models\FundFormulaProduct;

/**
 * @property FundFormulaProduct $resource
 */
class FundFormulaProductResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'id', 'product_id', 'record_type_key_multiplier',
        ]);
    }
}