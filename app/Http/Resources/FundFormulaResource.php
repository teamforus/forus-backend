<?php

namespace App\Http\Resources;

use App\Models\FundFormula;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property FundFormula $resource
 */
class FundFormulaResource extends JsonResource
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
            'type', 'amount', 'amount_locale', 'record_type_key',
        ]);
    }
}
