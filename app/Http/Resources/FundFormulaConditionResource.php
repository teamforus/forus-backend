<?php

namespace App\Http\Resources;

use App\Models\FundFormulaCondition;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @property-read FundFormulaCondition $resource
 */
class FundFormulaConditionResource extends BaseJsonResource
{
    public const array LOAD = [
        'record_type.translations',
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $condition = $this->resource;
        $recordType = $condition->record_type;
        $date = $recordType->isTypeDate() ? $condition->parseConditionDate($condition->value) : null;

        return [
            'record_type_key' => $recordType->key,
            'operator' => $condition->operator,
            'value' => $condition->value,
            'record_type_name' => $recordType->name,
            ...($date !== null ? $this->makeTimestamps([
                'value' => Carbon::createFromFormat('!Y-m-d', $date),
            ], dateOnly: true) : [
                'value_locale' => $condition->value,
            ]),
        ];
    }
}
