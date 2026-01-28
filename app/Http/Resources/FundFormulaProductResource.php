<?php

namespace App\Http\Resources;

use App\Models\FundFormulaProduct;
use Illuminate\Http\Request;

/**
 * @property FundFormulaProduct $resource
 */
class FundFormulaProductResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'product_id', 'record_type_key_multiplier',
        ]);
    }
}
