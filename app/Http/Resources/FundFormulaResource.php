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
        return array_merge($this->resource->only([
            'id', 'type', 'amount', 'amount_locale', 'record_type_key', 'updated_at',
        ]), [
            'record_type_name' => $this->resource->record_type?->name,
            'updated_at_locale' => format_datetime_locale($this->resource->updated_at),
        ]);
    }
}
