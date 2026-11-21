<?php

namespace App\Http\Resources;

use App\Models\FundFormula;
use Illuminate\Http\Request;

/**
 * @property FundFormula $resource
 */
class FundFormulaResource extends BaseJsonResource
{
    public const array LOAD = [
        'record_type.translation',
    ];

    public const array LOAD_NESTED = [
        'conditions' => FundFormulaConditionResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'id', 'type', 'amount', 'amount_locale', 'record_type_key',
        ]), [
            'record_type_name' => $this->resource->record_type?->name,
            'conditions' => FundFormulaConditionResource::collection($this->resource->conditions),
        ], $this->makeTimestamps($this->resource->only([
            'created_at', 'updated_at',
        ])));
    }
}
