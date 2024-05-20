<?php

namespace App\Http\Resources;

use App\Models\FundFormula;

/**
 * @property FundFormula $resource
 */
class FundFormulaResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'type', 'amount', 'amount_locale', 'record_type_key',
        ]), [
            'record_type_name' => $this->resource->record_type?->name,
        ], $this->makeTimestamps($this->resource->only([
            'created_at', 'updated_at',
        ])));
    }
}
